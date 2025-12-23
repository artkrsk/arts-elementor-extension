# Arts Elementor Extension Framework

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![Elementor](https://img.shields.io/badge/Elementor-compatible-red)
![License](https://img.shields.io/badge/license-GPL--3.0-green)

A powerful framework for building custom Elementor extensions with clean, reusable architecture.

## Overview

This framework provides a structured approach to developing Elementor extensions with:

- Base classes for widgets, skins, and tabs
- Manager classes for registration and organization
- Translation compatibility
- And more

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Elementor (latest version recommended)

## Installation

```bash
composer require arts/elementor-extension
```

## Usage Examples

### Creating a Custom Widget

```php
<?php

namespace YourNamespace\Widgets;

use Arts\ElementorExtension\Widgets\BaseWidget;
use Elementor\Controls_Manager;

class YourWidget extends BaseWidget {
  public function get_name() {
    return 'your-widget';
  }

  public function get_title() {
    return esc_html__('Your Widget', 'your-text-domain');
  }

  public function get_categories() {
    return ['your-category'];
  }

  protected function register_controls_content($tab) {
    $this->start_controls_section(
      'section_content',
      [
        'label' => esc_html__('Content', 'your-text-domain'),
        'tab' => $tab,
      ]
    );

    // Add your controls here

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();

    // Your widget output
    echo '<div class="your-widget">';
    // Use settings to customize output
    echo '</div>';
  }
}
```

### Creating a Custom Widget Skin

```php
<?php

namespace YourNamespace\Widgets\Skins;

use Arts\ElementorExtension\Widgets\BaseSkin;
use Elementor\Controls_Manager;

class YourSkin extends BaseSkin {
  public function get_id() {
    return 'your-skin';
  }

  public function get_title() {
    return esc_html__('Your Skin', 'your-text-domain');
  }

  public function register_controls(Controls_Stack $widget) {
    // Register skin-specific controls
  }

  public function render_skin() {
    $settings = $this->parent->get_settings_for_display();

    // Your skin-specific rendering
  }
}
```

### Adding WPML Translation Support

BaseWidget includes WPML compatibility. Override the translation methods:

```php
<?php

namespace YourNamespace\Widgets;

use Arts\ElementorExtension\Widgets\BaseWidget;

class YourWidget extends BaseWidget {
  // Other widget methods...

  protected function wpml_get_translatable_fields() {
    return [
      [
        'field' => 'title',
        'type' => 'Widget Title',
        'editor_type' => 'LINE'
      ],
      [
        'field' => 'description',
        'type' => 'Widget Description',
        'editor_type' => 'AREA'
      ],
    ];
  }
}
```

### Creating a Custom Elementor Tab

```php
<?php

namespace YourNamespace\Tabs;

use Arts\ElementorExtension\Tabs\BaseTab;
use Elementor\Controls_Manager;

class YourTab extends BaseTab {
  const TAB_ID = 'your-custom-tab';

  public function get_id() {
    return self::TAB_ID;
  }

  public function get_title() {
    return esc_html__('Your Tab', 'your-text-domain');
  }

  protected function register_tab_controls() {
    $this->start_controls_section(
      'section_your_tab',
      [
        'label' => esc_html__('Your Settings', 'your-text-domain'),
        'tab' => $this->get_id(),
      ]
    );

    $this->add_control(
      'your_setting',
      [
        'label' => esc_html__('Your Setting', 'your-text-domain'),
        'type' => Controls_Manager::SWITCHER,
        'default' => 'yes',
      ]
    );

    $this->end_controls_section();
  }
}
```

### Setting Up a Plugin

```php
<?php

namespace YourNamespace;

use Arts\ElementorExtension\Plugins\BasePlugin;

class YourPlugin extends BasePlugin {
  protected function get_default_config() {
    return [
      // Your plugin configuration
    ];
  }

  protected function get_default_strings() {
    return [
      // Your plugin strings
    ];
  }

  protected function get_managers_classes() {
    return [
      'widgets' => 'YourNamespace\Managers\Widgets',
      'categories' => 'YourNamespace\Managers\Categories',
      'tabs' => 'YourNamespace\Managers\Tabs',
      'editor' => 'YourNamespace\Managers\Editor',
    ];
  }

  protected function get_default_run_action() {
    return 'elementor/init';
  }

  protected function add_actions() {
    // Add custom actions
    return $this;
  }
}

// Initialize the plugin
YourPlugin::instance();
```

### Creating Manager Classes

#### Widget Manager

```php
<?php

namespace YourNamespace\Managers;

use Arts\ElementorExtension\Managers\Widgets as BaseWidgetsManager;

class Widgets extends BaseWidgetsManager {
  public function __construct($args = [], $strings = []) {
    parent::__construct($args, $strings);

    $this->widgets = [
      [
        'file' => __DIR__ . '/../Widgets/YourWidget.php',
        'class' => 'YourNamespace\Widgets\YourWidget',
      ],
      // Add more widgets
    ];
  }
}
```

#### Category Manager

```php
<?php

namespace YourNamespace\Managers;

use Arts\ElementorExtension\Managers\Categories as BaseCategoriesManager;

class Categories extends BaseCategoriesManager {
  public function __construct($args = [], $strings = []) {
    parent::__construct($args, $strings);

    $this->categories = [
      [
        'name' => 'your-category',
        'title' => esc_html__('Your Category', 'your-text-domain'),
        'icon' => 'eicon-apps',
      ],
      // Add more categories
    ];
  }
}
```

## Filters and Actions

The framework provides several filters and actions to extend its functionality:

### Filters

#### Registration Filters

```php
// Customize widgets registration
add_filter('arts/elementor_extension/widgets/widgets', function($widgets) {
  $widgets[] = [
    'file' => __DIR__ . '/Widgets/YourWidget.php',
    'class' => 'YourNamespace\Widgets\YourWidget',
  ];
  return $widgets;
});

// Customize categories registration
add_filter('arts/elementor_extension/widgets/categories', function($categories) {
  $categories[] = [
    'name' => 'your-category',
    'title' => 'Your Category',
    'icon' => 'eicon-apps',
  ];
  return $categories;
});

// Customize tabs registration
add_filter('arts/elementor_extension/tabs/tabs', function($tabs) {
  $tabs[] = [
    'file' => __DIR__ . '/Tabs/YourTab.php',
    'class' => 'YourNamespace\Tabs\YourTab',
  ];
  return $tabs;
});

// Add editor live settings
add_filter('arts/elementor_extension/editor/live_settings', function($settings) {
  $settings['your_setting'] = [
    'selector' => '.your-element',
    'property' => 'color',
  ];
  return $settings;
});
```

### Actions

```php
// Do something after widgets are registered
add_action('arts/elementor_extension/widgets/widgets_registered', function($instances, $manager) {
  // Your code here
}, 10, 2);

// Do something after categories are registered
add_action('arts/elementor_extension/widgets/categories_registered', function($categories, $manager) {
  // Your code here
}, 10, 2);

// Do something after tabs are registered
add_action('arts/elementor_extension/tabs/tabs_registered', function($references, $manager) {
  // Your code here
}, 10, 2);
```

## Architecture

The framework follows a modular architecture:

- **BaseWidget**: Foundation for all custom Elementor widgets
- **BaseSkin**: Foundation for widget skins
- **BaseTab**: Foundation for Elementor editor tabs
- **BasePlugin**: Core plugin setup and initialization
- **Managers**: Handle registration of components
- **Traits**: Reusable functionality (WPML)
