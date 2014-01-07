#include "php_maxminddb.h"

static const MMDB_entry_data_list_s *handle_entry_data_list(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value
    TSRMLS_DC);
static const MMDB_entry_data_list_s *handle_array(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value TSRMLS_DC);
static const MMDB_entry_data_list_s *handle_map(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value TSRMLS_DC);
static void handle_uint128(const MMDB_entry_data_list_s *entry_data_list,
                           zval *z_value TSRMLS_DC);
static void handle_uint64(const MMDB_entry_data_list_s *entry_data_list,
                          zval *z_value TSRMLS_DC);
static zend_class_entry * lookup_class(const char *name TSRMLS_DC);

#define CHECK_ALLOCATED(val)                  \
    if (!val ) {                              \
        zend_error(E_ERROR, "Out of memory"); \
        return;                               \
    }                                         \

#define THROW_EXCEPTION(name, ... )                                      \
    {                                                                    \
        zend_class_entry *exception_ce = lookup_class(name TSRMLS_CC);   \
        zend_throw_exception_ex(exception_ce, 0 TSRMLS_CC, __VA_ARGS__); \
    }                                                                    \


#if PHP_VERSION_ID < 50399
#define object_properties_init(zo, class_type)          \
    {                                                   \
        zval *tmp;                                      \
        zend_hash_copy((*zo).properties,                \
                       &class_type->default_properties, \
                       (copy_ctor_func_t)zval_add_ref,  \
                       (void *)&tmp,                    \
                       sizeof(zval *));                 \
    }
#endif

static zend_object_handlers maxminddb_obj_handlers;
static zend_class_entry *maxminddb_ce;

PHP_METHOD(MaxMind_Db_Reader, __construct){
    char *db_file = NULL;
    int name_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &db_file,
                              &name_len) == FAILURE) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "The constructor takes exactly one argument.");
        return;
    }

    if (0 != access(db_file, R_OK)) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "The file \"%s\" does not exist or is not readable.",
                        db_file);
        return;
    }

    MMDB_s *mmdb = (MMDB_s *)emalloc(sizeof(MMDB_s));
    uint16_t status = MMDB_open(db_file, MMDB_MODE_MMAP, mmdb);

    if (MMDB_SUCCESS != status) {
        THROW_EXCEPTION(
            PHP_MAXMINDDB_READER_EX_NS,
            "Error opening database file (%s). Is this a valid MaxMind DB file?",
            db_file);
        efree(mmdb);
        return;
    }

    maxminddb_obj *mmdb_obj = zend_object_store_get_object(getThis() TSRMLS_CC);
    mmdb_obj->mmdb = mmdb;
}

PHP_METHOD(MaxMind_Db_Reader, get){
    char *ip_address = NULL;
    int name_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &ip_address,
                              &name_len) == FAILURE) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "Method takes exactly one argument.");
        return;
    }

    const maxminddb_obj *mmdb_obj =
        (maxminddb_obj *)zend_object_store_get_object(
            getThis() TSRMLS_CC);

    MMDB_s *mmdb = mmdb_obj->mmdb;

    if (NULL == mmdb) {
        THROW_EXCEPTION("BadMethodCallException",
                        "Attempt to read from a closed MaxMind DB.");
        return;
    }

    int gai_error = 0;
    int mmdb_error = MMDB_SUCCESS;
    MMDB_lookup_result_s result =
        MMDB_lookup_string(mmdb, ip_address, &gai_error,
                           &mmdb_error);

    if (MMDB_SUCCESS != gai_error) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "The value \"%s\" is not a valid IP address.",
                        ip_address);
        return;
    }

    if (MMDB_SUCCESS != mmdb_error) {
        char *exception_name;
        if (MMDB_IPV6_LOOKUP_IN_IPV4_DATABASE_ERROR == mmdb_error) {
            exception_name = "InvalidArgumentException";
        } else {
            exception_name = PHP_MAXMINDDB_READER_EX_NS;
        }
        THROW_EXCEPTION(exception_name,
                        "Error looking up %s. %s",
                        ip_address, MMDB_strerror(mmdb_error));
        return;
    }

    MMDB_entry_data_list_s *entry_data_list = NULL;

    if (!result.found_entry) {
        RETURN_NULL();
    }

    int status = MMDB_get_entry_data_list(&result.entry, &entry_data_list);

    if (MMDB_SUCCESS != status) {
        THROW_EXCEPTION(PHP_MAXMINDDB_READER_EX_NS,
                        "Error while looking up data for %s. %s",
                        ip_address, MMDB_strerror(status));
        MMDB_free_entry_data_list(entry_data_list);
        return;
    } else if (NULL == entry_data_list) {
        THROW_EXCEPTION(
            PHP_MAXMINDDB_READER_EX_NS,
            "Error while looking up data for %s. Your database may be corrupt or you have found a bug in libmaxminddb.",
            ip_address);
        return;
    }

    handle_entry_data_list(entry_data_list, return_value TSRMLS_CC);
    MMDB_free_entry_data_list(entry_data_list);
}

PHP_METHOD(MaxMind_Db_Reader, metadata){
    if (ZEND_NUM_ARGS() != 0) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "Method takes no arguments.");
        return;
    }

    const maxminddb_obj *const mmdb_obj =
        (maxminddb_obj *)zend_object_store_get_object(
            getThis() TSRMLS_CC);

    if (NULL == mmdb_obj->mmdb) {
        THROW_EXCEPTION("BadMethodCallException",
                        "Attempt to read from a closed MaxMind DB.");
        return;
    }

    const char *const name = ZEND_NS_NAME(PHP_MAXMINDDB_READER_NS, "Metadata");
    zend_class_entry *metadata_ce = lookup_class(name TSRMLS_CC);

    object_init_ex(return_value, metadata_ce);

    zval *metadata_array;
    ALLOC_INIT_ZVAL(metadata_array);

    MMDB_entry_data_list_s *entry_data_list;
    MMDB_get_metadata_as_entry_data_list(mmdb_obj->mmdb, &entry_data_list);

    handle_entry_data_list(entry_data_list, metadata_array TSRMLS_CC);
    MMDB_free_entry_data_list(entry_data_list);
    zend_call_method_with_1_params(&return_value, metadata_ce,
                                   &metadata_ce->constructor,
                                   ZEND_CONSTRUCTOR_FUNC_NAME,
                                   NULL,
                                   metadata_array);
    zval_ptr_dtor(&metadata_array);
}

PHP_METHOD(MaxMind_Db_Reader, close){
    if (ZEND_NUM_ARGS() != 0) {
        THROW_EXCEPTION("InvalidArgumentException",
                        "Method takes no arguments.");
        return;
    }

    maxminddb_obj *mmdb_obj = (maxminddb_obj *)zend_object_store_get_object(
        getThis() TSRMLS_CC);

    if (NULL == mmdb_obj->mmdb) {
        THROW_EXCEPTION("BadMethodCallException",
                        "Attempt to close a closed MaxMind DB.");
        return;
    }
    MMDB_close(mmdb_obj->mmdb);
    efree(mmdb_obj->mmdb);
    mmdb_obj->mmdb = NULL;
}

static const MMDB_entry_data_list_s *handle_entry_data_list(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value
    TSRMLS_DC)
{
    switch (entry_data_list->entry_data.type) {
    case MMDB_DATA_TYPE_MAP:
        return handle_map(entry_data_list, z_value TSRMLS_CC);
    case MMDB_DATA_TYPE_ARRAY:
        return handle_array(entry_data_list, z_value TSRMLS_CC);
    case MMDB_DATA_TYPE_UTF8_STRING:
        ZVAL_STRINGL(z_value,
                     (char *)entry_data_list->entry_data.utf8_string,
                     entry_data_list->entry_data.data_size,
                     1);
        break;
    case MMDB_DATA_TYPE_BYTES:
        ZVAL_STRINGL(z_value, (char *)entry_data_list->entry_data.bytes,
                     entry_data_list->entry_data.data_size, 1);
        break;
    case MMDB_DATA_TYPE_DOUBLE:
        ZVAL_DOUBLE(z_value, entry_data_list->entry_data.double_value);
        break;
    case MMDB_DATA_TYPE_FLOAT:
        ZVAL_DOUBLE(z_value, entry_data_list->entry_data.float_value);
        break;
    case MMDB_DATA_TYPE_UINT16:
        ZVAL_LONG(z_value, entry_data_list->entry_data.uint16);
        break;
    case MMDB_DATA_TYPE_UINT32:
        ZVAL_LONG(z_value, entry_data_list->entry_data.uint32);
        break;
    case MMDB_DATA_TYPE_BOOLEAN:
        ZVAL_BOOL(z_value, entry_data_list->entry_data.boolean);
        break;
    case MMDB_DATA_TYPE_UINT64:
        handle_uint64(entry_data_list, z_value TSRMLS_CC);
        break;
    case MMDB_DATA_TYPE_UINT128:
        handle_uint128(entry_data_list, z_value TSRMLS_CC);
        break;
    case MMDB_DATA_TYPE_INT32:
        ZVAL_LONG(z_value, entry_data_list->entry_data.int32);
        break;
    default:
        THROW_EXCEPTION(PHP_MAXMINDDB_READER_EX_NS,
                        "Invalid data type arguments: %d",
                        entry_data_list->entry_data.type);
        return NULL;
    }
    return entry_data_list;
}

static const MMDB_entry_data_list_s *handle_map(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value TSRMLS_DC)
{
    array_init(z_value);
    const uint32_t map_size = entry_data_list->entry_data.data_size;

    uint i;
    for (i = 0; i < map_size && entry_data_list; i++ ) {
        entry_data_list = entry_data_list->next;

        char *key =
            estrndup((char *)entry_data_list->entry_data.utf8_string,
                     entry_data_list->entry_data.data_size);
        if (NULL == key) {
            THROW_EXCEPTION(PHP_MAXMINDDB_READER_EX_NS,
                            "Invalid data type arguments");
            return NULL;
        }

        entry_data_list = entry_data_list->next;
        zval *new_value;
        ALLOC_INIT_ZVAL(new_value);
        entry_data_list = handle_entry_data_list(entry_data_list,
                                                 new_value TSRMLS_CC);
        add_assoc_zval(z_value, key, new_value);
        efree(key);
    }
    return entry_data_list;
}

static const MMDB_entry_data_list_s *handle_array(
    const MMDB_entry_data_list_s *entry_data_list,
    zval *z_value TSRMLS_DC)
{
    const uint32_t size = entry_data_list->entry_data.data_size;

    array_init(z_value);

    uint i;
    for (i = 0; i < size && entry_data_list; i++) {
        entry_data_list = entry_data_list->next;
        zval *new_value;
        ALLOC_INIT_ZVAL(new_value);
        entry_data_list = handle_entry_data_list(entry_data_list,
                                                 new_value TSRMLS_CC);
        add_next_index_zval(z_value, new_value);
    }
    return entry_data_list;
}

static void handle_uint128(const MMDB_entry_data_list_s *entry_data_list,
                           zval *z_value TSRMLS_DC)
{
    uint64_t high = 0;
    uint64_t low = 0;
#if MMDB_UINT128_IS_BYTE_ARRAY
    int i;
    for (i = 0; i < 8; i++) {
        high = (high << 8) | entry_data_list->entry_data.uint128[i];
    }

    for (i = 8; i < 16; i++) {
        low = (low << 8) | entry_data_list->entry_data.uint128[i];
    }
#else
    high = entry_data_list->entry_data.uint128 >> 64;
    low = (uint64_t)entry_data_list->entry_data.uint128;
#endif

    char *num_str;
    spprintf(&num_str, 0, "0x%016" PRIX64 "%016" PRIX64, high, low);
    CHECK_ALLOCATED(num_str);

    ZVAL_STRING(z_value, num_str, 1);
    efree(num_str);
}

static void handle_uint64(const MMDB_entry_data_list_s *entry_data_list,
                          zval *z_value TSRMLS_DC)
{
    // We return it as a string because PHP uses signed longs
    char *int_str;
    spprintf(&int_str, 0, "%" PRIu64,
             entry_data_list->entry_data.uint64 );
    CHECK_ALLOCATED(int_str);

    ZVAL_STRING(z_value, int_str, 0);
}

static zend_class_entry *lookup_class(const char *name TSRMLS_DC)
{
    zend_class_entry **ce;
    if (FAILURE ==
        zend_lookup_class(name, strlen(name),
                          &ce TSRMLS_CC)) {
        zend_error(E_ERROR, "Class %s not found", name);
    }
    return *ce;
}

static void maxminddb_free_storage(void *object TSRMLS_DC)
{
    maxminddb_obj *obj = (maxminddb_obj *)object;
    if (obj->mmdb != NULL) {
        MMDB_close(obj->mmdb);
        efree(obj->mmdb);
    }

    zend_hash_destroy(obj->std.properties);
    FREE_HASHTABLE(obj->std.properties);

    efree(obj);
}

static zend_object_value maxminddb_create_handler(
    zend_class_entry *type TSRMLS_DC)
{
    zend_object_value retval;

    maxminddb_obj *obj = (maxminddb_obj *)emalloc(sizeof(maxminddb_obj));
    memset(obj, 0, sizeof(maxminddb_obj));
    obj->std.ce = type;

    ALLOC_HASHTABLE(obj->std.properties);
    zend_hash_init(obj->std.properties, 0, NULL, ZVAL_PTR_DTOR, 0);

    object_properties_init(&(obj->std), type);

    retval.handle = zend_objects_store_put(obj, NULL,
                                           maxminddb_free_storage,
                                           NULL TSRMLS_CC);
    retval.handlers = &maxminddb_obj_handlers;

    return retval;
}

/* *INDENT-OFF* */
static zend_function_entry maxminddb_methods[] = {
    PHP_ME(MaxMind_Db_Reader, __construct, NULL,
           ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(MaxMind_Db_Reader, close,    NULL, ZEND_ACC_PUBLIC)
    PHP_ME(MaxMind_Db_Reader, get,      NULL, ZEND_ACC_PUBLIC)
    PHP_ME(MaxMind_Db_Reader, metadata, NULL, ZEND_ACC_PUBLIC)
    { NULL, NULL, NULL }
};
/* *INDENT-ON* */

PHP_MINIT_FUNCTION(maxminddb){
    zend_class_entry ce;

    INIT_CLASS_ENTRY(ce, PHP_MAXMINDDB_READER_NS, maxminddb_methods);
    maxminddb_ce = zend_register_internal_class(&ce TSRMLS_CC);
    maxminddb_ce->create_object = maxminddb_create_handler;
    maxminddb_ce->ce_flags |= ZEND_ACC_FINAL | ZEND_ACC_ABSTRACT;
    memcpy(&maxminddb_obj_handlers,
           zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    maxminddb_obj_handlers.clone_obj = NULL;

    return SUCCESS;
}

zend_module_entry maxminddb_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_MAXMINDDB_EXTNAME,
    NULL,
    PHP_MINIT(maxminddb),
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_MAXMINDDB_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_MAXMINDDB
ZEND_GET_MODULE(maxminddb)
#endif
