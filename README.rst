SunLight CMS console
####################

This library provides various CLI commands to help develop
and maintain a SunLight CMS project.

.. contents::


Installation
************

The `SunLight CMS skeleton <https://github.com/sunlight-cms/skeleton>`_
provides this console already pre-configured.

If you want to add this console into a project not based on the skeleton,
you can do so using `Composer <https://getcomposer.org/>`_:

.. code:: bash

   composer require sunlight-cms/console


Configuration
*************

The console will read configuration from your project's *composer.json* file.

::

  {
      // ...
      "extra": {
          "sunlight-console": {
              "cms": {
                  "version": "latest"
              }
          }
      }
  }


Supported options
=================

cms
---

Options related to how the CMS files are downloaded.


version
^^^^^^^

Required

CMS version to download.

If ``zip-url`` is set in `cms.archive <archive_>`_, the CMS will be downloaded
from the specified URL. In that case the version string can have any format.

If `cms.archive <archive_>`_ is configured to use a git repository, the version
string can have these formats:

- ``latest`` - will download the latest supported version
  (and also update ``cms.version`` to that version)
- ``8.0.0`` - will download the exact tagged version
- ``~8.0.0`` - will find and download a tagged version matching
  the `constraint <https://getcomposer.org/doc/articles/versions.md>`_
- ``dev-master`` - will download the latest version of the specified branch


archive
^^^^^^^

Default: configured to use https://github.com/sunlight-cms/sunlight-cms

::

  "archive": {
      "zip-url": null,
      "zip-paths-prefix": "sunlight-cms-%version%/",
      "git-url": "https://github.com/sunlight-cms/sunlight-cms.git",
      "git-branch-zip-url": "https://github.com/sunlight-cms/sunlight-cms/archive/refs/heads/%version%.zip",
      "git-tag-zip-url": "https://github.com/sunlight-cms/sunlight-cms/archive/refs/tags/v%version%.zip",
      "git-tag-pattern": "v8*"
  }

- ``zip-url`` - CMS archive download URL or ``null`` to use a git repository
- ``zip-paths-prefix`` - prefix of the paths in the archive (can be empty)
- ``git-url`` - git repository URL to scan for tags or ``null``
- ``git-branch-zip-url`` - URL that serves a branch as a .zip file or ``null``
- ``git-tag-zip-url`` - URL that serves a tag as a .zip file or ``null``
- ``git-tag-pattern`` - pattern for tag matching (used in git ls-remote) or ``null``

.. NOTE::

   ``zip-paths-prefix``, ``git-branch-zip-url`` and ``git-tag-zip-url`` may contain
   a ``%version%`` placeholder which will be replaced by the specified `cms.version <version_>`_


plugins
^^^^^^^

Default: no plugins

List of plugins to extract from the CMS archive. Templates will not be overwritten.

::

  "plugins": {
      "extend": [
          "devkit",
          "codemirror",
          "lightbox"
      ],
      "templates": [
          "default",
          "blank"
      ],
      "languages": [
          "cs",
          "en"
      ]
  }


installer
^^^^^^^^^

Default: ``true``

Boolean value indicating whether to extract the *install/* directory
from the CMS archive.

.. NOTE::

   The installer will only be extracted if the CMS files don't already exist.


------------

commands
--------

Default: ``[]``

Map of custom commands to add to the console.

Example:

::

  "commands": {
      "example.foo": {
          "class": "Example\\FooCommand"
      },
      "example.bar": {
          "class": "Example\\BarCommand"
      }
  }

The command classes must extend ``SunlightConsole\Command``.

.. NOTE::

    See *src/Config/ServiceConfig.php* for a full list of supported options.


Commands from other packages
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Other installed Composer packages can define commands the same way
in their *composer.json*.

In this case, only the ``commands`` option will be read and all others
will be ignored.

The package can have any `type <https://getcomposer.org/doc/04-schema.md#type>`_ other than "project".


is-fresh-project
----------------

Default: ``false``

Boolean value indicating that this is a fresh project.

If set to ``true``, the next time the CMS files are downloaded some additional
updates will be made to *composer.json*:

- name, description and license will be unset
- the ``is-fresh-project`` option will be unset
- if a semver version constraint (or "latest") has been used to locate the CMS archive,
  the `cms.version <version_>`_ will be automatically changed to the installed version number


Usage
*****

.. code:: bash

  bin/console <command> [options] [args]


Commands
========

The console provides the following commands by default:

(Run ``bin/console`` or ``bin/console help`` to show more information
about the available commands.)

::

  backup.create          create a backup
  cache.clear            clear the cache
  cms.download           download CMS files
  cms.info               show information about the CMS
  cms.patch              apply a patch to CMS files in the project
  config.create          create config.php with default contents
  config.dump            dump config.php contents
  config.set             modify an option in config.php
  db.dump                dump database
  db.import              import a SQL dump
  db.query               execute a SQL query
  log.monitor            continuously print out log entries
  log.search             search log entries
  plugin.action          perform a plugin action (or list actions if no action is given)
  plugin.install         install plugin from a ZIP file or an URL
  plugin.list            list all plugins
  plugin.show            show information about a plugin
  project.dump-config    dump resolved project configuration
  user.reset-password    reset password for the given user
  help                   show help

.. TIP::

   You can also pass ``--help`` to any command to show help for it.


Command name matching
=====================

It is possible to pass partial command names if it is not ambiguous.

For example:

- ``bin/console ca.cl`` will run the ``cache.clear`` command
- ``bin/console pl.s devkit`` will run the ``plugin.show`` command
