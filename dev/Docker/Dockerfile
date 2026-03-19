FROM debian:bookworm-slim
SHELL ["/bin/bash", "-c"]

RUN apt-get update && apt-get install -y libsodium-dev dnsutils iproute2 net-tools host sed grep default-mysql-server unzip zip wget lsb-release libsodium-dev libxslt1-dev build-essential libbz2-dev libfreetype6-dev libicu-dev libzip-dev libxslt-dev libtidy-dev libedit-dev libreadline-dev libonig-dev libjpeg-dev libpng-dev libcurl4-openssl-dev  libbz2-dev zlib1g zlib1g-dev sqlite3 libsqlite3-dev libssl-dev libxml2-dev pkg-config automake tree autotools-dev libtool build-essential curl git libedit-dev autoconf-archive

RUN git clone --single-branch --branch master https://github.com/phpenv/phpenv /root/.phpenv
RUN git clone https://github.com/php-build/php-build /root/.phpenv/plugins/php-build

RUN git config --global user.email "ex@example.com" && git config --global user.name "example" && cd /root/.phpenv/plugins/php-build \
    && git remote add convenient https://github.com/convenient/php-build/ \
    && git fetch convenient \
    && git merge convenient/fix-php-72 \
    && cd -

RUN echo 'eval "$(phpenv init -)"' >> /root/.bashrc
ENV PATH="/root/.phpenv/bin:$PATH"

RUN source /root/.bashrc && PHP_BUILD_CONFIGURE_OPTS="--with-sodium" phpenv install 8.5.4  && phpenv global 8.5.4  && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/8.5.4/etc/php.ini && php --version
RUN source /root/.bashrc && PHP_BUILD_INSTALL_EXTENSION="libsodium=1.0.7" phpenv install 7.4.29 && phpenv global 7.4.29 && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/7.4.29/etc/php.ini && php --version
RUN source /root/.bashrc && PHP_BUILD_CONFIGURE_OPTS="--with-sodium" phpenv install 8.3.13  && phpenv global 8.3.13  && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/8.3.13/etc/php.ini && php --version
RUN source /root/.bashrc && PHP_BUILD_CONFIGURE_OPTS="--with-sodium" phpenv install 8.4.3  && phpenv global 8.4.3  && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/8.4.3/etc/php.ini && php --version

ENV LDFLAGS=""
ENV CPPFLAGS=""

RUN source /root/.bashrc && PHP_BUILD_CONFIGURE_OPTS="--with-sodium" phpenv install 8.1.6  && phpenv global 8.1.6  && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/8.1.6/etc/php.ini && php --version

ENV CXX="g++ -DTRUE=1 -DFALSE=0"
ENV CC="gcc -DTRUE=1 -DFALSE=0"
ENV CFLAGS="-Wno-discarded-qualifiers"
ENV CXXFLAGS="-Wno-discarded-qualifiers"

RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-1.1.1i.tar.gz && \
    tar xzf openssl-1.1.1i.tar.gz && \
    cd openssl-1.1.1i && \
    ./Configure --prefix=/opt/openssl-1.1.1i/bin -fPIC -shared linux-x86_64 && \
    make -j 8 && \
    make install

ENV PKG_CONFIG_PATH=/opt/openssl-1.1.1i/bin/lib/pkgconfig

RUN cd /root/.phpenv/plugins/php-build \
    && git reset --hard origin/master \
    && git fetch convenient \
    && git merge convenient/fix-php-72 \
    && cd -

RUN source /root/.bashrc && phpenv install 7.2.34 && phpenv global 7.2.34 && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/7.2.34/etc/php.ini && php --version

RUN source /root/.bashrc && PHP_BUILD_CONFIGURE_OPTS="--with-sodium" phpenv install 8.2.25  && phpenv global 8.2.25  && sed -i 's/memory_limit = 128M/memory_limit = 1024M/' /root/.phpenv/versions/8.2.25/etc/php.ini && php --version

RUN for ver in $(phpenv versions --bare); do /root/.phpenv/versions/$ver/bin/php --version; done

RUN for ver in $(phpenv versions --bare); do smallver="$(echo "$ver" | cut -d '.' -f 1,2)"; ln -s /root/.phpenv/versions/$ver /root/.phpenv/versions/$smallver ; done

RUN source /root/.bashrc && wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet && mv composer.phar /root/.phpenv/bin/composer2 && /root/.phpenv/bin/composer2 self-update --2
RUN source /root/.bashrc && wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet && mv composer.phar /root/.phpenv/bin/composer1 && /root/.phpenv/bin/composer1 self-update --1