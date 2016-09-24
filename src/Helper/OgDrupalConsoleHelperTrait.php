<?php

namespace Drupal\og\Helper;

use Symfony\Component\Console\Helper\HelperSet;
use Drupal\Console\Helper\HelperTrait;
use Drupal\Console\Helper\TwigRendererHelper;

trait OgDrupalConsoleHelperTrait {

  use HelperTrait;

  /**
   * @var \Symfony\Component\Console\Helper\HelperSet
   */
  protected $helperSet;

  public $dir;

  public function getHelperSet($input = null) {
    if (!$this->helperSet) {
      $stringHelper = $this->getMockBuilder('Drupal\Console\Helper\StringHelper')
        ->disableOriginalConstructor()
        ->setMethods(['createMachineName'])
        ->getMock();

      $stringHelper->expects($this->any())
        ->method('createMachineName')
        ->will($this->returnArgument(0));

      $validator = $this->getMockBuilder('Drupal\Console\Helper\ValidatorHelper')
        ->disableOriginalConstructor()
        ->setMethods(['validateModuleName'])
        ->getMock();

      $validator->expects($this->any())
        ->method('validateModuleName')
        ->will($this->returnArgument(0));

      $translator = $this->getTranslatorHelper();

      $chain = $this
        ->getMockBuilder('Drupal\Console\Helper\ChainCommandHelper')
        ->disableOriginalConstructor()
        ->setMethods(['addCommand', 'getCommands'])
        ->getMock();

      $drupal = $this
        ->getMockBuilder('Drupal\Console\Helper\DrupalHelper')
        ->setMethods(['isBootable', 'getDrupalRoot'])
        ->getMock();

      $siteHelper = $this
        ->getMockBuilder('Drupal\Console\Helper\SiteHelper')
        ->disableOriginalConstructor()
        ->setMethods(['setModulePath', 'getModulePath'])
        ->getMock();

      $siteHelper->expects($this->any())
        ->method('getModulePath')
        ->will($this->returnValue($this->dir));

      $this->helperSet = new HelperSet(
        [
          'renderer' => new TwigRendererHelper(),
          'string' => $stringHelper,
          'validator' => $validator,
          'translator' => $translator,
          'site' => $siteHelper,
          'chain' => $chain,
          'drupal' => $drupal,
        ]
      );
    }

    return $this->helperSet;
  }

  public function getTranslatorHelper() {
    $translatorHelper = $this
      ->getMockBuilder('Drupal\Console\Helper\TranslatorHelper')
      ->disableOriginalConstructor()
      ->setMethods(['loadResource', 'trans', 'getMessagesByModule', 'writeTranslationsByModule'])
      ->getMock();

    $translatorHelper->expects($this->any())
      ->method('getMessagesByModule')
      ->will($this->returnValue([]));

    return $translatorHelper;
  }

  protected function getGenerator() {
    return $this
      ->getMockBuilder('Drupal\Console\Generator\ModuleGenerator')
      ->disableOriginalConstructor()
      ->setMethods(['generate'])
      ->getMock();
  }

}