# Field Encrypt

The Field Encrypt module allows fields to be stored in an encrypted state 
rather than in plain text and allows these fields to be decrypted when loaded 
for viewing.

## Configuring Field Encrypt

The field encrypt module provides support for core textual fields to be 
encrypted and decrypted. You will need to install the Key 
(https://www.drupal.org/project/key) and Encrypt 
(https://www.drupal.org/project/encrypt) modules for this to work.
 
Make sure you have set up a secure key and encryption profile. See README files
of these modules for more information on how to configure them securely.

- For each field you wish to encrypt, go to the "Storage settings" form.
- Check the "Encrypted" checkbox to have this field automatically encrypted and
  decrypted.
- Select an encryption profile to use from the "Encryption profile" list.
- Save the field storage settings.

Notes:
- It's possible that multiple FieldEncryptionProvider plugins are registered
to handle the field type you are trying to encrypt. In that case, an extra 
select element "Provider" will be shown, allowing you to select which provider
(and accompanying encryption service) you wish to use.

- If the "Encrypted" checkbox is not shown, this means your field type is not
supported for encrypted. You should install or create a module that provides a 
FieldEncryptionProvider for that particular field type.

## Architecture Documentation

There is an architecture overview available in /documentation. This is available
as an image file as well as a draw.io xml file. This should give a general idea 
of how each of the portions of the module function and how the module hooks into
Drupal and it's plugin system.

### FieldEncryptProcessEntities Service

The main function of the FieldEncryptProcessEntities Service is to provide a way
to process entities.

In Drupal 8, it is recommended that all processing on fields be done at the 
entity level. As such, the service primarily takes entities as inputs and 
processes those fields in order to either decrypt encrypted fields or encrypt 
fields before storage.

The `encryptEntity()` and `decryptEntity()` methods perform these actions. 
Additionally, after we have changed the storage settings (enabled / disabled
 encryption), we must process existing fields. `encryptStoredField()` and 
 `decryptStoredField()` provide that functionality.

Inside the service, we iterate over each field and then each of the fields 
values. For example, the `text_with_summary` field type has a `value` and a 
`summary` value. The encryption itself is then handled by other services. 
For example, text encryption is handled by the `encryption` service as part of
the `encrypt` module.

The mapping of field types to services is done with our plugin system.

### FieldEncryptionProvider Plugin System

Plugins can be defined to map field types to an encryption / decryption service.
Plugins can be created by using the provided 
`Plugin/FieldEncryptionProvider/CoreStrings.php` plugin as a reference. 
This plugin defines the supported fields in its annotation, and loads the 
encryption service used to process these strings.
By using the plugin system, we allow other modules to define encryption 
processes for non-string values such as numbers, files, and images. 
These plugins can also define encryption process for string values of custom 
field types.

### Encryption Services
The field_encrypt module uses services to encrypt and decrypt field values. 
The module relies on the `encrypt` module for string processing. Other modules 
may define their own services and map fields to those services with
 `FieldEncryptionProvider` plugins. They should extend the 
 `FieldEncryptionProviderBase` class.

### Encrypt Field Storage Third Party Setting
In Drupal 8, the field storage settings (field base in Drupal 7) are stored in
 configuration management using a Configuration Entity. 
 Extending configuration entities is done by providing `Third Party Settings` 
 and using the associated methods.

We provide this setting to all fields using the 
`config/schema/field_encrypt.schema.yml` file.

### Configuring Fields to use Encryption
While this creates the setting, we need to modify the field storage form so that
we can set / change this value. We hook into the form system with 
`hook_form_alter()` in our `.module` file.

### Updating Stored Field Values
In addition to adding this new value to the form, we add a function to handle 
the form submission which will respond to changes in the setting value.
This way, when the setting is changed, we can process stored values and 
encrypt / decrypt to match the new setting. This encryption / decryption is 
handled by the `FieldEncryptProcessEntities` service with the 
`encryptStoredField()` and `decryptStoredField()` methods.
