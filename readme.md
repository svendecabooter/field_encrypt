# Field Encrypt

The Field Encrypt module allows fields to be stored in an encrypted state 
rather than in plain text and allows these fields to be decrypted when loaded 
for viewing.

## Configuring Field Encrypt

The field encrypt module provides support for Drupal fields to be encrypted and 
decrypted. You will need to install the Key (https://www.drupal.org/project/key)
and Encrypt (https://www.drupal.org/project/encrypt) modules for this to work.
 
Make sure you have set up a secure key and encryption profile. See README files
of these modules for more information on how to configure them securely.

- For each field you wish to encrypt, go to the "Storage settings" form.
- Check the "Encrypted" checkbox to have this field automatically encrypted and
  decrypted.
- Select which field properties to encrypt. Reasonable defaults are provided for
  core field types.
- Select an encryption profile to use from the "Encryption profile" list.
- Save the field storage settings.

You can change the default properties that will be selected per field type, on a
per-site basis:

- Go to Administration > Configuration > System > Field Encrypt settings
  (/admin/config/system/field_encrypt).
- Choose which properties should be selected by default for encryption, when
  setting up field encryption for the available field types.
 
Note: changes to the settings form do not persist to the field config or field 
value encryption - these are merely default settings that are set ONLY when 
a field is set up for encryption the first time.

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
`summary` value. The encryption itself is then handled by the encryption service
of the Encrypt module.

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
