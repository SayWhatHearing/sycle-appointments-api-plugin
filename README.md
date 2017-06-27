# sycle-appointments-api-plugin

Ripped on https://github.com/mattyza/starter-plugin

## Todo

* Optional logging - Lars
* Update routine - Michael
* Clean up on deactivate - Lars

# SYCLE API PLUGIN

## Settings
The settings page is accessed under Settings->Sycle Appointements

Sycle Username - Your username for Sycle API. Mandatory.
Sycle Password - Your password for Sycle API. Mandatory.
Sycle Subdomain - Set your subdomain for Sycle API.
Google Places API Key - To use geolocation, this API key from Google is needed.
Logging - Logs interactions and shows debug information on the settings page below.
Remove all data - Turn this on to remove all data when deactivating the plugin.



## Shortcodes
To have some flexibility in using the plugin I implemented a couple of shortcodes.

General note - Each clinic that is listed is contained in a form that links to the location url of the location to continue booking process there.

This happens via the clinic id that is returned via Sycle. Internal code looks up the landing page via the post meta field **sycle_clinic_id** that matches.

Each location also had open graph data added, this can help search engines pick up location data for each clinic.

### [sycle]
The

### [sycleclinicslist]

This shortcode shows the available clinics for this user. To reduce load time, the shotcode itself just outputs the html container and a check in sycle.js detects if the list is shown.

If the shortcode is displayed, a request goes via wp-ajax to return the clinics list.

### [syclebooking]

Paramaters:
id - optional. If this is not parsed, the shortcode will look for the post meta value sycle_clinic_id on the current page and use that if found.  If not, an error is shown.

Example: [syclebooking id="2803-9506"]

Notes: The id parameter is optional. If an id parameter is included, then that will be used.

If there is no id= paramater, the plugin looks to see if the clinic id is passed via $_POST['sycle_clinic_id'].

If there is no id= paramater or passed via POST, final step is to look up the "sycle_clinic_id" meta value for the current page the shortcode is on.

If none of the 3 methods to look up the clinic id is succesfull, an error will be displayed. Only for admins, regular users just have an empty output.

## Code
There are built in actions - e.g.

This action contains two parameters:
action - to separate different actions, eg. “look for clinic”
data - the data submitted

