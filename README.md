Yii2 Dynamic Sitemap Generator
==============================
An extension to generate sitemap dynamically for indexing purpose.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fierydevs/yii2-sitemap "*"
```

or add

```
"fierydevs/yii2-sitemap": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :
No parameter is mandatory. Default values are mentioned in the below code.

```php
<?= \fierydevs\sitemap\SitemapGenerator::widget([
			'output_file' => 'sitemap.xml',
			'site' => \yii\helpers\Url::base(true),
			'cli' => false,
			'frequency' => 'weekly', 
			'priority' => 0.5, 
			'ignore_empty_content_type' => false, 
			'version' => 1.0,
		]); ?>
```