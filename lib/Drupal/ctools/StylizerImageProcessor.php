<?php

/**
 * @file
 * Definition of Drupal\ctools\StylizerImageProcessor.
 */

namespace Drupal\ctools;

class StylizerImageProcessor {
  var $workspace = NULL;
  var $name = NULL;

  var $workspaces = array();

  var $message_log = array();
  var $error_log = array();

  function execute($path, $plugin, $settings) {
    $this->path = $path;
    $this->plugin = $plugin;
    $this->settings = $settings;
    $this->palette = $settings['palette'];

    if (is_string($plugin['actions']) && function_exists($plugin['actions'])) {
      $actions = $plugin['actions']($plugin, $settings);
    }
    else if (is_array($plugin['actions'])) {
      $actions = $plugin['actions'];
    }

    if (!empty($actions) && is_array($actions)) {
      foreach ($plugin['actions'] as $action) {
        $command = 'command_' . array_shift($action);
        if (method_exists($this, $command)) {
          call_user_func_array(array($this, $command), $action);
        }
      }
    }

    // Clean up buffers.
    foreach ($this->workspaces as $name => $workspace) {
      imagedestroy($this->workspaces[$name]);
    }
  }

  function log($message, $type = 'normal') {
    $this->message_log[] = $message;
    if ($type == 'error') {
      $this->error_log[] = $message;
    }
  }

  function set_current_workspace($workspace) {
    $this->log("Set current workspace: $workspace");
    $this->workspace = &$this->workspaces[$workspace];
    $this->name = $workspace;
  }

  /**
   * Create a new workspace.
   */
  function command_new($name, $width, $height) {
    $this->log("New workspace: $name ($width x $height)");
    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = imagecreatetruecolor($width, $height);
    $this->set_current_workspace($name);

    // Make sure the new workspace has a transparent color.

    // Turn off transparency blending (temporarily)
    imagealphablending($this->workspace, FALSE);

    // Create a new transparent color for image
    $color = imagecolorallocatealpha($this->workspace, 0, 0, 0, 127);

    // Completely fill the background of the new image with allocated color.
    imagefill($this->workspace, 0, 0, $color);

    // Restore transparency blending
    imagesavealpha($this->workspace, TRUE);

  }

  /**
   * Create a new workspace a file.
   *
   * This will make the new workspace the current workspace.
   */
  function command_load($name, $file) {
    $this->log("New workspace: $name (from $file)");
    if (!file_exists($file)) {
      // Try it relative to the plugin
      $file = $this->plugin['path'] . '/' . $file;
      if (!file_exists($file)) {
        $this->log("Unable to open $file");
        return;
      }
    }

    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = imagecreatefrompng($file);
    $this->set_current_workspace($name);
  }

  /**
   * Create a new workspace using the properties of an existing workspace
   */
  function command_new_from($name, $workspace) {
    $this->log("New workspace: $name from existing $workspace");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    // Clean up if there was already a workspace there.
    if (isset($this->workspaces[$name])) {
      imagedestroy($this->workspaces[$name]);
    }

    $this->workspaces[$name] = $this->new_image($this->workspace[$workspace]);
    $this->set_current_workspace($name);
  }

  /**
   * Set the current workspace.
   */
  function command_workspace($name) {
    $this->log("Set workspace: $name");
    if (empty($this->workspaces[$name])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }
    $this->set_current_workspace($name);
  }

  /**
   * Copy the contents of one workspace into the current workspace.
   */
  function command_merge_from($workspace, $x = 0, $y = 0) {
    $this->log("Merge from: $workspace ($x, $y)");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    $this->merge($this->workspaces[$workspace], $this->workspace, $x, $y);
  }

  function command_merge_to($workspace, $x = 0, $y = 0) {
    $this->log("Merge to: $workspace ($x, $y)");
    if (empty($this->workspaces[$workspace])) {
      $this->log("Workspace $name does not exist.", 'error');
      return;
    }

    $this->merge($this->workspace, $this->workspaces[$workspace], $x, $y);
    $this->set_current_workspace($workspace);
  }

  /**
   * Blend an image into the current workspace.
   */
  function command_merge_from_file($file, $x = 0, $y = 0) {
    $this->log("Merge from file: $file ($x, $y)");
    if (!file_exists($file)) {
      // Try it relative to the plugin
      $file = $this->plugin['path'] . '/' . $file;
      if (!file_exists($file)) {
        $this->log("Unable to open $file");
        return;
      }
    }

    $source = imagecreatefrompng($file);

    $this->merge($source, $this->workspace, $x, $y);

    imagedestroy($source);
  }

  function command_fill($color, $x, $y, $width, $height) {
    $this->log("Fill: $color ($x, $y, $width, $height)");
    imagefilledrectangle($this->workspace, $x, $y, $x + $width, $y + $height, _color_gd($this->workspace, $this->palette[$color]));
  }

  function command_gradient($from, $to, $x, $y, $width, $height, $direction = 'down') {
    $this->log("Gradient: $from to $to ($x, $y, $width, $height) $direction");

    if ($direction == 'down') {
      for ($i = 0; $i < $height; ++$i) {
        $color = _color_blend($this->workspace, $this->palette[$from], $this->palette[$to], $i / ($height - 1));
        imagefilledrectangle($this->workspace, $x, $y + $i, $x + $width, $y + $i + 1, $color);
      }
    }
    else {
      for ($i = 0; $i < $width; ++$i) {
        $color = _color_blend($this->workspace, $this->palette[$from], $this->palette[$to], $i / ($width - 1));
        imagefilledrectangle($this->workspace, $x + $i, $y, $x + $i + 1, $y + $height, $color);
      }
    }
  }

  /**
   * Colorize the current workspace with the given location.
   *
   * This uses simple color blending to colorize the image.
   *
   * @todo it is possible that this colorize could allow different methods for
   * determining how to blend colors?
   */
  function command_colorize($color, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $whole_image = TRUE;
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }
    $this->log("Colorize: $color ($x, $y, $width, $height)");

    $c = _color_unpack($this->palette[$color]);

    imagealphablending($this->workspace, FALSE);
    imagesavealpha($this->workspace, TRUE);

    // If PHP 5 use the nice imagefilter which is faster.
    if (!empty($whole_image) && version_compare(phpversion(), '5.2.5', '>=') && function_exists('imagefilter')) {
      imagefilter($this->workspace, IMG_FILTER_COLORIZE, $c[0], $c[1], $c[2]);
    }
    else {
      // Otherwise we can do it the brute force way.
      for ($j = 0; $j < $height; $j++) {
        for ($i = 0; $i < $width; $i++) {
          $current = imagecolorsforindex($this->workspace, imagecolorat($this->workspace, $i, $j));
          $new_index = imagecolorallocatealpha($this->workspace, $c[0], $c[1], $c[2], $current['alpha']);
          imagesetpixel($this->workspace, $i, $j, $new_index);
        }
      }
    }
  }

  /**
   * Colorize the current workspace with the given location.
   *
   * This uses a color replacement algorithm that retains luminosity but
   * turns replaces all color with the specified color.
   */
  function command_hue($color, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $whole_image = TRUE;
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }
    $this->log("Hue: $color ($x, $y, $width, $height)");

    list($red, $green, $blue) = _color_unpack($this->palette[$color]);

    // We will create a monochromatic palette based on the input color
    // which will go from black to white.

    // Input color luminosity: this is equivalent to the position of the
    // input color in the monochromatic palette
    $luminosity_input = round(255 * ($red + $green + $blue) / 765); // 765 = 255 * 3

    // We fill the palette entry with the input color at itscorresponding position
    $palette[$luminosity_input]['red'] = $red;
    $palette[$luminosity_input]['green'] = $green;
    $palette[$luminosity_input]['blue'] = $blue;

    // Now we complete the palette, first we'll do it tothe black, and then to
    // the white.

    // From input to black
    $steps_to_black = $luminosity_input;

    // The step size for each component
    if ($steps_to_black) {
      $step_size_red = $red / $steps_to_black;
      $step_size_green = $green / $steps_to_black;
      $step_size_blue = $blue / $steps_to_black;

      for ($i = $steps_to_black; $i >= 0; $i--) {
        $palette[$steps_to_black-$i]['red'] = $red - round($step_size_red * $i);
        $palette[$steps_to_black-$i]['green'] = $green - round($step_size_green * $i);
        $palette[$steps_to_black-$i]['blue'] = $blue - round($step_size_blue * $i);
      }
    }

    // From input to white
    $steps_to_white = 255 - $luminosity_input;

    if ($steps_to_white) {
      $step_size_red = (255 - $red) / $steps_to_white;
      $step_size_green = (255 - $green) / $steps_to_white;
      $step_size_blue = (255 - $blue) / $steps_to_white;
    }
    else {
      $step_size_red=$step_size_green=$step_size_blue=0;
    }

    // The step size for each component
    for ($i = ($luminosity_input + 1); $i <= 255; $i++) {
      $palette[$i]['red'] = $red + round($step_size_red * ($i - $luminosity_input));
      $palette[$i]['green'] = $green + round($step_size_green * ($i - $luminosity_input));
      $palette[$i]['blue']= $blue + round($step_size_blue * ($i - $luminosity_input));
    }

    // Go over the specified area of the image and update the colors.
    for ($j = $x; $j < $height; $j++) {
      for ($i = $y; $i < $width; $i++) {
        $color = imagecolorsforindex($this->workspace, imagecolorat($this->workspace, $i, $j));
        $luminosity = round(255 * ($color['red'] + $color['green'] + $color['blue']) / 765);
        $new_color = imagecolorallocatealpha($this->workspace, $palette[$luminosity]['red'], $palette[$luminosity]['green'], $palette[$luminosity]['blue'], $color['alpha']);
        imagesetpixel($this->workspace, $i, $j, $new_color);
      }
    }
  }

  /**
   * Take a slice out of the current workspace and save it as an image.
   */
  function command_slice($file, $x = NULL, $y = NULL, $width = NULL, $height = NULL) {
    if (!isset($x)) {
      $x = $y = 0;
      $width = imagesx($this->workspace);
      $height = imagesy($this->workspace);
    }

    $this->log("Slice: $file ($x, $y, $width, $height)");

    $base = basename($file);
    $image = $this->path . '/' . $base;

    $slice = $this->new_image($this->workspace, $width, $height);
    imagecopy($slice, $this->workspace, 0, 0, $x, $y, $width, $height);

    // Make sure alphas are saved:
    imagealphablending($slice, FALSE);
    imagesavealpha($slice, TRUE);

    // Save image.
    $temp_name = drupal_tempnam('temporary://', 'file');

    imagepng($slice, drupal_realpath($temp_name));
    file_unmanaged_move($temp_name, $image);
    imagedestroy($slice);

    // Set standard file permissions for webserver-generated files
    @chmod(realpath($image), 0664);

    $this->paths[$file] = $image;
  }

  /**
   * Prepare a new image for being copied or worked on, preserving transparency.
   */
  function &new_image(&$source, $width = NULL, $height = NULL) {
    if (!isset($width)) {
      $width = imagesx($source);
    }

    if (!isset($height)) {
      $height = imagesy($source);
    }

    $target = imagecreatetruecolor($width, $height);
    imagealphablending($target, FALSE);
      imagesavealpha($target, TRUE);

    $transparency_index = imagecolortransparent($source);

    // If we have a specific transparent color
    if ($transparency_index >= 0) {
      // Get the original image's transparent color's RGB values
      $transparent_color = imagecolorsforindex($source, $transparency_index);

      // Allocate the same color in the new image resource
      $transparency_index = imagecolorallocate($target, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);

      // Completely fill the background of the new image with allocated color.
      imagefill($target, 0, 0, $transparency_index);

      // Set the background color for new image to transparent
      imagecolortransparent($target, $transparency_index);
    }
    // Always make a transparent background color for PNGs that don't have one allocated already
    else {
      // Create a new transparent color for image
      $color = imagecolorallocatealpha($target, 0, 0, 0, 127);

      // Completely fill the background of the new image with allocated color.
      imagefill($target, 0, 0, $color);
    }

    return $target;
  }

  /**
   * Merge two images together, preserving alpha transparency.
   */
  function merge(&$from, &$to, $x, $y) {
    // Blend over template.
    $width = imagesx($from);
    $height = imagesy($from);

    // Re-enable alpha blending to make sure transparency merges.
    imagealphablending($to, TRUE);
    imagecopy($to, $from, $x, $y, 0, 0, $width, $height);
    imagealphablending($to, FALSE);
  }
}
