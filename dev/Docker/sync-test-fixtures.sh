#!/bin/bash
# Synchronise explicitly named existing or new source fixture files into an
# existing upgraded Magento test instance. The pre-upgrade vendor_orig snapshot
# is deliberately never touched.
source /root/.bashrc
set -euo pipefail
set -f

: "${ID:?ID must be set by the Docker environment}"

if [[ ! "$ID" =~ ^[A-Za-z0-9._-]+$ ]]; then
    echo "Invalid fixture ID: $ID" >&2
    exit 2
fi

readonly repository_root=/src
readonly instance_root="${repository_root}/dev/instances/magento${ID}"

if [[ ! -d "$instance_root" ]]; then
    echo "Magento fixture does not exist: $instance_root" >&2
    exit 2
fi

if [[ -z "${SYNC_PATHS//[[:space:]]/}" && -z "${DELETE_PATHS//[[:space:]]/}" ]]; then
    echo 'Set SYNC_PATHS and/or DELETE_PATHS to whitespace-separated fixture paths.' >&2
    exit 2
fi

validate_relative_path() {
    local path="$1"
    local segment

    [[ -n "$path" && "$path" != /* ]] || return 1

    IFS=/ read -r -a path_segments <<< "$path"
    for segment in "${path_segments[@]}"; do
        [[ -n "$segment" && "$segment" != . && "$segment" != .. ]] || return 1
    done
}

reject_symlink_path() {
    local path="$1"

    # Missing components are allowed for new files, but no existing component
    # below the repository mount may redirect reads or writes through a symlink.
    while [[ "$path" == "$repository_root" || "$path" == "$repository_root/"* ]]; do
        if [[ -L "$path" ]]; then
            echo "Refusing symlink in fixture path: $path" >&2
            return 1
        fi
        [[ "$path" == "$repository_root" ]] && break
        path="${path%/*}"
    done
}

package_destination() {
    local composer_file="$1"

    reject_symlink_path "$composer_file" || return 1
    php -r '
        try {
            $composer = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            fwrite(STDERR, "Cannot read composer package name: " . $exception->getMessage() . PHP_EOL);
            exit(1);
        }

        $name = $composer["name"] ?? null;
        if (!is_string($name) || !preg_match("#^[a-z0-9][a-z0-9._-]*/[a-z0-9][a-z0-9._-]*$#", $name)) {
            fwrite(STDERR, "Fixture composer.json must contain a valid package name." . PHP_EOL);
            exit(1);
        }

        echo $name;
    ' "$composer_file"
}

destination_for_path() {
    local fixture_path="$1"
    local fixture_name
    local suffix
    local fixture_root
    local package_name
    local destination

    if ! validate_relative_path "$fixture_path"; then
        echo "Invalid relative fixture path: $fixture_path" >&2
        return 1
    fi

    case "$fixture_path" in
        dev/Test*/*) ;;
        *)
            echo "Only paths rooted in a direct dev/Test*/ fixture are supported: $fixture_path" >&2
            return 1
            ;;
    esac

    fixture_name="${fixture_path#dev/}"
    fixture_name="${fixture_name%%/*}"
    suffix="${fixture_path#dev/${fixture_name}/}"
    fixture_root="${repository_root}/dev/${fixture_name}"

    reject_symlink_path "${repository_root}/${fixture_path}" || return 1
    if [[ "$fixture_name" == TestModule && "$suffix" == app/* ]]; then
        destination="${instance_root}/${suffix}"
    elif [[ -f "${fixture_root}/composer.json" ]]; then
        package_name="$(package_destination "${fixture_root}/composer.json")" || return 1
        destination="${instance_root}/vendor/${package_name}/${suffix}"
    else
        echo "No destination mapping for fixture path: $fixture_path" >&2
        return 1
    fi

    case "$destination" in
        "${instance_root}"/*) ;;
        *)
            echo "Refusing destination outside the selected fixture: $destination" >&2
            return 1
            ;;
    esac

    case "$destination" in
        *"/vendor_orig/"*)
            echo "Refusing to write to vendor_orig: $destination" >&2
            return 1
            ;;
    esac

    reject_symlink_path "$destination" || return 1
    printf '%s\n' "$destination"
}

sync_one() {
    local fixture_path="$1"
    local source="${repository_root}/${fixture_path}"
    local destination

    [[ -f "$source" ]] || {
        echo "Fixture source file does not exist: $fixture_path" >&2
        return 1
    }
    destination="$(destination_for_path "$fixture_path")" || return 1
    mkdir -p "$(dirname "$destination")"
    cp --preserve=mode,timestamps "$source" "$destination"
    echo "Synced $fixture_path"
}

delete_one() {
    local fixture_path="$1"
    local destination

    destination="$(destination_for_path "$fixture_path")" || return 1
    [[ -f "$destination" || -L "$destination" ]] || {
        echo "Fixture destination file does not exist: $fixture_path" >&2
        return 1
    }
    rm -- "$destination"
    echo "Deleted $fixture_path"
}

read -r -a sync_paths <<< "${SYNC_PATHS:-}"
read -r -a delete_paths <<< "${DELETE_PATHS:-}"

# Check the other write/delete targets before changing any fixture files.
for path in \
    "${instance_root}/generated/code" \
    "${instance_root}/generated/metadata" \
    "${instance_root}/var/cache" \
    "${instance_root}/var/page_cache" \
    "${instance_root}/vendor.patch"; do
    reject_symlink_path "$path"
done

for fixture_path in "${sync_paths[@]}"; do
    sync_one "$fixture_path"
done

for fixture_path in "${delete_paths[@]}"; do
    delete_one "$fixture_path"
done

# Developer-mode Magento can regenerate these on demand. Avoid database work,
# Composer changes, module enablement and DI compilation during a fast fixture edit.
rm -rf -- \
    "${instance_root}/generated/code" \
    "${instance_root}/generated/metadata" \
    "${instance_root}/var/cache" \
    "${instance_root}/var/page_cache"

set +e
(
    cd "$instance_root"
    diff -ur -N vendor_orig/ vendor/ > vendor.patch
)
diff_status=$?
set -e

if [[ $diff_status -ne 0 && $diff_status -ne 1 ]]; then
    echo 'Could not regenerate vendor.patch.' >&2
    exit "$diff_status"
fi

echo "Regenerated ${instance_root}/vendor.patch"
