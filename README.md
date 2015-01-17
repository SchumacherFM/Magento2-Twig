Magento2-Twig
============

Twig template engine for Magento2

Frontend Integration
--------------------

Your template files must have the file extension `.twig` to get automatically recognized.

In your layout xml files please specify the new template

```
<referenceBlock name="top.links">
    <block class="Magento\Theme\Block\Html\Header" template="html/header.twig" name="header" as="header" before="-">
        <arguments>
            <argument name="show_part" xsi:type="string">welcome dude</argument>
        </arguments>
    </block>
</referenceBlock>
```

#### Access helper methods

Write in your `.twig` file:

```
{{ helper("Magento\\Core\\Helper\\Url").getHomeUrl() }}
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
    {{ getChildHtml() }}
{% endif %}
```

Tests
-----

@todo

Installation via Composer
------------

Add the following to the require section of your Magento 2 `composer.json` file

    "schumacherfm/mage2-twig": "dev-master"

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

#### 0.1.0

- Initial release

License
-------

OSL-30

Author
------

[Cyrill Schumacher](http://cyrillschumacher.com)

[My pgp public key](http://www.schumacher.fm/cyrill.asc)
