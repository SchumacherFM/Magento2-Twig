Magento2 Twig Template Engine
============

[Twig](http://twig.sensiolabs.org) template engine for Magento2.

This template engine is meant to be used additionally to the `.phtml` files and does not 
provide any `.twig` template file.

A use case would be if you write your first Magento2 module you can require this package
and write all your template files in Twig.

Events & Configuration
-------------

The Twig template engine class dispatches two events so that you can modify Twig.

Event `twig_loader` with event object `loader`. You can set `loader` any other class which implements
`Twig_LoaderInterface`. [http://twig.sensiolabs.org/doc/api.html#loaders](http://twig.sensiolabs.org/doc/api.html#loaders)

Event `twig_init` with event object `twig`. You can add here more functions, filters, tags, etc.
[http://twig.sensiolabs.org/doc/advanced.html](http://twig.sensiolabs.org/doc/advanced.html)

Configuration options can be found Stores -> Settings -> Configuration -> Advanced -> Developer -> Twig.

Frontend Integration
--------------------

Your template files must have the file extension `.twig` to get automatically recognized.

In your layout xml files or blocks please specify the new template

```
<referenceBlock name="top.links">
    <block class="Magento\Theme\Block\Html\Header" template="html/header.twig" name="header" as="header" before="-">
        <arguments>
            <argument name="show_part" xsi:type="string">welcome</argument>
        </arguments>
    </block>
</referenceBlock>
```

#### Example header.phtml converted to header.twig

```
<?php switch ($this->getShowPart()):
    case 'welcome': ?>
        <li class="greet welcome"><?php echo $this->getWelcome() ?></li>
    <?php break; ?>
    <?php case 'other': ?>
        <?php echo $this->getChildHtml(); ?>
    <?php break; ?>
<?php endswitch; ?>
```

```
{% if getShowPart() == 'welcome' %}
    <li class="greet welcome">{{ getWelcome() }}</li>
{% endif %}

{% if getShowPart() == 'other' %}
    {{ getChildHtml()|raw }}
{% endif %}
```

#### Example breadcrumbs.phtml converted to breadcrumbs.twig

```
<?php if ($crumbs && is_array($crumbs)) : ?>
<div class="breadcrumbs">
    <ul class="items">
        <?php foreach ($crumbs as $crumbName => $crumbInfo) : ?>
            <li class="item <?php echo $crumbName ?>">
            <?php if ($crumbInfo['link']) : ?>
                <a href="<?php echo $crumbInfo['link'] ?>" title="<?php echo $this->escapeHtml($crumbInfo['title']) ?>">
                    <?php echo $this->escapeHtml($crumbInfo['label']) ?>
                </a>
            <?php elseif ($crumbInfo['last']) : ?>
                <strong><?php echo $this->escapeHtml($crumbInfo['label']) ?></strong>
            <?php else: ?>
                <?php echo $this->escapeHtml($crumbInfo['label']) ?>
            <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
```

```
{% if crumbs %}
<div class="breadcrumbs">
    <ul class="items">
    {% for crumbName,crumbInfo in crumbs %}
        <li class="item {{ crumbName }}">
            {% if crumbInfo.link %}
                <a href="{{ crumbInfo.link }}" title="{{ crumbInfo.title }}">
                    {{ crumbInfo.label }}
                </a>
            {% elseif crumbInfo.last %}
                <strong>{{ crumbInfo.label }}</strong>
            {% else %}
                {{ crumbInfo.label }}
            {% endif %}
        </li>
    {% endfor %}
    </ul>
</div>
{% endif %}
```

#### Access helper methods

Write in your `.twig` file:

```
{{ helper("Magento\\Core\\Helper\\Url").getHomeUrl() }}
```

Tests
-----

@todo

Installation via Composer
------------

Add the following to the require section of your Magento 2 `composer.json` file

    "schumacherfm/magento-twig": "dev-master"

additionally add the following in the repository section

        "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/SchumacherFM/Magento2-Twig.git"
        }
    ]
    
run `composer update`

add the following to `app/etc/config.php`

    'SchumacherFM_Twig'=>1

Compatibility
-------------

- Magento >= 2
- php >= 5.4.0

Support / Contribution
----------------------

Report a bug using the issue tracker or send us a pull request.

Instead of forking I can add you as a Collaborator IF you really intend to develop on this module. Just ask :-)

I am using that model: [A successful Git branching model](http://nvie.com/posts/a-successful-git-branching-model/)

For versioning have a look at [Semantic Versioning 2.0.0](http://semver.org/)

History
-------

#### 2.0.0

- Added Magento 2.4.0 compatibility
- Removed helper functions from `app/functions.php` since the file is no longer available in Magento 2.4
- Removed deprecated function `layoutBlock` from twig environment
- Updated to twig to 3.0.* 

License
-------

OSL-30

Author
------

[Cyrill Schumacher](http://cyrillschumacher.com)

[My pgp public key](http://www.schumacher.fm/cyrill.asc)
