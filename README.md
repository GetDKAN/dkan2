# DKAN 8.x-2.x Prototype

DKAN Open Data Portal built on Drupal 8. See NOTES.md for additional information.

## Requirements

1) Install dkan-tools (get the ```dkan2``` branch): [https://github.com/GetDKAN/dkan-tools](https://github.com/GetDKAN/dkan-tools)
1) Checkout the dkan2 branch
1) In ``dkan-tools/bin/app.php`` There is a variable called ``drupalVersion``. Change its value to ``"V8"``.
1) Setup and start the proxy:
    1) Add `dkan.local` to `/etc/hosts`
    1) Start the proxy: 
    ``docker run -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock:ro jwilder/nginx-proxy`` 


## Installation

1) Create a directory for your project: ``mkdir <directory-name> && cd <directory-name>``
1) Initialize your project with dkan-tools: ``dktl init``
1) In ``src/make/composer.json`` for the dkan2 version use ``dev-development`` instead of ``dev-master``
1) Get Drupal: ``dktl drupal:get <drupal-version>``
1) Get Drupal dependencies, and install DKAN: ``dktl drupal:make``
1) Install DKAN: ``dktl drush si -y``
1) Access the site: ``dktl drush uli --uri=dkan.local``


## Developing with and Compiling Front End

The current demo uses the Interra catalog front-end. To setup locally:

```
git clone git@github.com:interra/catalog-generate.git --branch dkan-demo
```

Either create a new site:

```
plop
```
or use ``dkan-demo``.

To run the dev server: 

* update the "devUrl" in the config.yml file to your Drupal 8 dkan backend.
* run ``node cli.js run-dev-dll; node cli.js run-dev dkan-demo``

To build for prod:

* ``node cli.js build-site dkan-demo``

This will build the site in ``build/dkan-demo``
