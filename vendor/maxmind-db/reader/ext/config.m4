PHP_ARG_ENABLE(maxminddb,
    [Whether to enable the MaxMind DB Reader extension],
    [  --enable-maxminddb      Enable MaxMind DB Reader extension support])

if test $PHP_MAXMINDDB != "no"; then
    PHP_CHECK_LIBRARY(maxminddb, MMDB_open)

    # The -Wunused-parameter and -Wmissing-field-initializers warnings
    # interfere with the PHP macros
    CFLAGS="$CFLAGS -Wall -Wextra -Wno-unused-parameter -Wno-missing-field-initializers"

    PHP_ADD_LIBRARY(maxminddb, 1, MAXMINDDB_SHARED_LIBADD)
    PHP_SUBST(MAXMINDDB_SHARED_LIBADD)

    PHP_NEW_EXTENSION(maxminddb, maxminddb.c, $ext_shared)
fi
