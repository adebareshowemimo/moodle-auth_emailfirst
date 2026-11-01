# Nigeria State and LGA profile fields

Emailfirst can render two Moodle custom user profile fields as dependent
Nigeria State and Local Government Area (LGA) dropdowns. The bundled data
contains the 36 states, the Federal Capital Territory, and all 774 LGAs.
No external API is used by the registration page.

The location names are derived from the MIT-licensed Nigeria Geo dataset; see
`data/LICENSE-NIGERIA-GEO.txt` for attribution and license details.

## Moodle setup

In **Site administration > Users > User profile fields**, create two
**Text input** fields:

1. Display name **State**, short name `state`, display on signup **Yes**.
2. Display name **Local government area**, short name `lga`, display on signup **Yes**.

The short names must be exactly `state` and `lga` in lowercase. Mark either
field required if it should be mandatory during registration. Text input is
recommended because Emailfirst turns the inputs into dropdowns and stores the
selected names as normal custom-profile-field values.

The controls appear on `/auth/emailfirst/signup.php` only after both fields
exist and both have **Display on signup page** enabled. Selecting a state
immediately limits the LGA dropdown to that state. The server validates the
pair again before creating the account.

Emailfirst's Mustache registration page also renders every other custom
profile field that has **Display on signup page** enabled. Core text, menu,
checkbox, textarea, date/time, and social fields are rendered using their
configured labels, required state, defaults, and menu choices.

After deploying or upgrading the plugin, complete Moodle's upgrade process and
purge caches so the new AMD module and language strings are available.
