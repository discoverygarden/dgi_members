# DGI Members

## Introduction

Provides a new context condition and logic to allow an objects member to be evaluated based on
the presence of a given taxonomy term.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/Islandora/islandora)

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Usage

#### Compound Objects
This module would typically be used to create a display for compound objects. The included
condition will evaluate a compounds first member (by order of weight on the 'Members' section),
or by the provided query param (should be 'active_member').
* Create a compound object display if one does not already exist, by configuring a new condition
to to evaluate if node has term ('Node has term with URI => 'Compound Object', for example).
* Configure each islandora display's on the context page (such as 'Open Seadragon', 'PDFjs Viewer', etc.)
to also evaluate this modules provided condition, 'Compound active member node has term with URI',
checking for the same term present on the child object (either first member sorted by weight, or
the node id from a query param, should be 'active_member').
* Add a new contextual filter to the 'Repository Item: Top Viewer' display in the 'Repository Item Content Blocks'
view of 'Content: Collection(s)'.
* Change the 'Pager' settings to only show a specific number of items, 1.

This module will allow switching view modes for the current member of the compound object,
not just the compound object itself (provided by the menu router). 

## Troubleshooting/Issues

Having problems or solved one? contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
