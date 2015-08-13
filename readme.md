# Field Encrypt
The Field Encrypt module allows fields to be stored in an encrypted state rather than in plain text and allows these fields to be decrypted when loaded for viewing.

## Documentation
There is an architecture overview available in /documentation. This is available as an image file as well as a draw.io xml file. This should give a general idea of how each of the portions of the module function and how the module hooks into Drupal and it's plugin system.

## FieldEncryptProcessEntities Service
The main function of the FieldEncryptProcessEntities Service is to provide a way to process entities.

In Drupal 8, it is recommended that all processing on fields be done at the entity level. As such, the service primarily takes entities as inputs and processes those fields in order to either decrypt encrypted fields or encrypt fields before storage.

The `encrypt_entity()` and `decrypt_entity()` methods perform these actions. Additionally, after we have changed the storage settings (enabled / disabled encryption), we must process existing fields. `encrypt_stored_field()` and `decrypt_stored_field()` provide that functionality.

Inside the service, we iterate over each field and then each of the fields values. For example, the `text_with_summary` field type has a `value` and a `summary` value. The encryption itself is then handled by other services. For example, text encryption is handled by the `encryption` service as part of the `encrypt` module.

The mapping of field types to values and to services is done with our plugin system.

## FieldEncryptMap Plugin System
The plugin system provides a mapping of field types to field values and these values to services that use `encrypt()` and `decrypt()` methods.

Plugins can be created by using the provided `Plugin/FieldEncryptMap/CoreStrings.php` plugin as a reference. This plugin loads the service used to process strings and provides a map using `getMap()`. This map provides an array who's keys are field types. Each of these items is itself an array of associated field values that can be encrypted and their associated services.

If an encryption service does not provide the `encrypt()` and `decrypt()` methods, an in-between service should be created and loaded in the plugin in order to implement that service.

By using the plugin system, we allow other modules to define encryption processes for non-string values such as numbers, files, and images. These plugins can also define encryption process for string values of custom field types.

## Encryption Services
The field_encrypt module uses services to encrypt and decrypt field values. The module relies on the `encrypt` module for string processing. Other modules may define their own services and map fields to those services with `FieldEncryptMap` plugins.

Each service should provide an `encrypt()` and `decrypt()` function. If they do not, a service should be create that acts an an interface between the service and the `FieldEncryptMap`.

## encrypt Field Storage Third Party Setting
In Drupal 8, the field storage settings (field base in Drupal 7) are stored in configuration management using a Configuration Entity. Extending configuration entities is done by providing `Third Party Settings` and using the associated methods.

We provide this setting to all fields using the `config/schema/field_encrypt.schema.yml` file.

## Configuring Fields to use Encryption
While this creates the setting, we need to modify the field storage form so that we can set / change this value. We hook into the form system with `hook_form_alter()` in our `.module` file.

## Updating Stored Field Values
In addition to adding this new value to the form, we add a function to handle the form submission which will respond to changes in the setting value. This way, when the setting is changed, we can process stored values and encrypt / decrypt to match the new setting. This encryption / decryption is handled by the `FieldEncryptProcessEntities` service with the `encrypt_stored_field()` and `decrypt_stored_field()` methods.
