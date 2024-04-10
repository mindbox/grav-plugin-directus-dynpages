# Directus Dynpages Plugin

The **Directus Dynpages** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). It is used to create pages dynamically based on Flex Objects created by the [directus2 plugin](https://github.com/mindbox/grav-plugin-directus2).

## Usage

Lets assume you have a collection 'workshops'. and want to display these a list and with a detail page for each entry. You already set up the directus2 plugin and the Flex Objects Directory called 'zbr_workshops' is now available.

In your pages folder you have the following files:  
user/pages/workshops/workshop-list.md
user/pages/workshops/01.workshops/workshop-detail.md

The listing can be done with the usual Flex Object logic you prefer. The part where the detail pages are generated on the fly/dynamically it where this plugin comes in.

First, [configure](#configuration) the plugin. In this case `routes` need to have an entry `/workshops`.

The `workshop-detail.md` is pretty simple:

```md
---
title: 'Workshop'
flex:
  collection: zbr_workshops
  id: null
---
```

The plugin will now search for the slug in the request within the collection.  
So in example.com/workshops/team-building we look for `team-building` in the `slugField` of the Flex Objects Directory `zbr_workshops`.

If it finds a correlating entry it cretes this page on the fly and will show the `workshop-detail.html.twig` template. Also the id field in the Frontmatter will be filled with the key of the corresponding object, so you can query it in the template.

Here is an idea of what this template may look like:

```twig
{% extends 'partials/base.html.twig' %}

{# generic content from directus #}
{% set post = grav.get('flex').object( header.flex.id, header.flex.collection )%}

{% block content %}
<h1 class="title">{{ post.zbr_title }}</h1>
<div class="workshop__meta wrapping">
    <div class="wrapped">
        {% if post.zbr_duration -%}
        <p class="item duration">
            <span class="label">Dauer:</span>
            {{ post.zbr_duration }}
        </p>
…
```

In case you want to use this for a news section, you may consider an RSS feed, take a look in the `examples` folder for an example template.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/directus-dynpages/directus-dynpages.yaml` to `user/config/plugins/directus-dynpages.yaml` and only edit that copy.

Here is an example configuration and an explanation of available options:

```yaml
enabled: true
slugField: zbr_slug
routes:
  - /arbeiten
  - /blog
  - /leistungen/workshops
feed:
  limit: 10
  title: 'zebra|group Blog'
  description: 'Aktuelles aus der zebra|group'
```

`slugField` names the field in which the slug of the entry is located. This must be the smae over all collections.  
`routes` defines the routes in which the plugin should hook into.  
`feed` provides some settings for an RSS feed. It's optional, because you can hard code these things in the feed template though.

## Installation

### Installation as dependency (skeleton)

To install the plugin automaticall with `bin/grav install`, add the following to the git section of your `user/.dependecies` file:

```
git:
    directus2:
        url: https://github.com/mindbox/grav-plugin-directus2
        path: user/plugins/directus2
        branch: main
    directus-dynpages:
        url: https://github.com/mindbox/grav-plugin-directus-dynpages
        path: user/plugins/directus-dynpages
        branch: main
```

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `directus2`. You can find these files on [GitHub](https://github.com/mindbox/grav-plugin-directus-dynpages).

You should now have all the plugin files under

    /your/site/grav/user/plugins/directus-dynpages
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/mindbox/grav-plugin-directus-dynpages/blob/main/blueprints.yaml).
