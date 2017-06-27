# sycle-appointments-api-plugin

Ripped on https://github.com/mattyza/starter-plugin

## Todo

* Optional logging - Lars
* Update routine - Michael
* Clean up on deactivate - Lars

# SYCLE API PLUGIN

## Settings
The settings page allows you to see


## Shortcodes
To have some flexibility in using the plugin I implemented a couple of shortcodes.

General note - Each clinic that is listed is contained in a form that links to the location url of the location to continue booking process there.

This happens via the clinic id that is returned via Sycle. Internal code looks up the landing page via the post meta field **sycle_clinic_id** that matches.

Each location also had open graph data added, this can help search engines pick up location data for each clinic.

### [sycle]
The 

### [sycledØ**]
This shortcode shows the available clinics for this user. To reduce load time, the shotcode itself just outputs the html container and a check in sycle.js detects if the list is shown.

If the shortcode is displayed, a request goes via wp-ajax to return the clinics list.


### [syclebooking]

Paramaters:
clinic_id - optional. If this is not parsed, the shortcode will look for the post meta value sycle_clinic_id on the current page and use that if found.  If not, an error is shown.



## Code
There are built in actions - e.g.

This action contains two parameters:
action - to separate different actions, eg. “look for clinic”
data - the data submitted 

