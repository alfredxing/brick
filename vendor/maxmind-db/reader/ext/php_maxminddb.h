#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_interfaces.h>
#include "Zend/zend_exceptions.h"
#include <maxminddb.h>

#ifdef ZTS
#include <TSRM.h>
#endif

#define __STDC_FORMAT_MACROS
#include <inttypes.h>


#ifndef PHP_MAXMINDDB_H
#define PHP_MAXMINDDB_H 1
#define PHP_MAXMINDDB_VERSION "0.2.0"
#define PHP_MAXMINDDB_EXTNAME "maxminddb"
#define PHP_MAXMINDDB_NS ZEND_NS_NAME("MaxMind", "Db")
#define PHP_MAXMINDDB_READER_NS ZEND_NS_NAME(PHP_MAXMINDDB_NS, "Reader")
#define PHP_MAXMINDDB_READER_EX_NS        \
    ZEND_NS_NAME(PHP_MAXMINDDB_READER_NS, \
                 "InvalidDatabaseException")

PHP_FUNCTION(maxminddb);

extern zend_module_entry maxminddb_module_entry;
#define phpext_maxminddb_ptr &maxminddb_module_entry

#endif

typedef struct _maxminddb_obj maxminddb_obj;

struct _maxminddb_obj {
    zend_object std;
    MMDB_s *mmdb;
};
