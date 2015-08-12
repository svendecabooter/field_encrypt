# Field Encrypt
The Field Encrypt module ...

## FieldEncryptProcessEntities Service


## FieldEncryptMap Plugin System


## Encryption Services
The field_encrypt module uses services to encrypt and decrypt field values. The module relies on the `encrypt` module for string processing. Other modules may define their own services and map fields to those services with `FieldEncryptMap` plugins.

Each service should provide an `encrypt()` and `decrypt()` function. If they do not, a service should be create that acts an an interface between the service and the `FieldEncryptMap`.

## encrypt Field Storage Third Party Setting


## Configuring Fields to use Encryption


## Updating Stored Field Values


