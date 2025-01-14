<?php

/**
 * @package    Grav\Common
 *
 * @copyright  Copyright (c) 2015 - 2021 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common;

use Grav\Common\Form\FormFlash;
use Grav\Events\SessionStartEvent;
use Grav\Plugin\Form\Forms;
use function is_string;

/**
 * Class Session
 * @package Grav\Common
 */
class Session extends \Grav\Framework\Session\Session
{
    /** @var bool */
    protected $autoStart = false;

    /**
     * @return \Grav\Framework\Session\Session
     * @deprecated 1.5 Use ->getInstance() method instead.
     */
    public static function instance()
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated since Grav 1.5, use ->getInstance() method instead', E_USER_DEPRECATED);

        return static::getInstance();
    }

    /**
     * Initialize session.
     *
     * Code in this function has been moved into SessionServiceProvider class.
     *
     * @return void
     */
    public function init()
    {
        if ($this->autoStart && !$this->isStarted()) {
            $this->start();

            $this->autoStart = false;
        }
    }

    /**
     * @param bool $auto
     * @return $this
     */
    public function setAutoStart($auto)
    {
        $this->autoStart = (bool)$auto;

        return $this;
    }

    /**
     * Returns attributes.
     *
     * @return array Attributes
     * @deprecated 1.5 Use ->getAll() method instead.
     */
    public function all()
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated since Grav 1.5, use ->getAll() method instead', E_USER_DEPRECATED);

        return $this->getAll();
    }

    /**
     * Checks if the session was started.
     *
     * @return bool
     * @deprecated 1.5 Use ->isStarted() method instead.
     */
    public function started()
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated since Grav 1.5, use ->isStarted() method instead', E_USER_DEPRECATED);

        return $this->isStarted();
    }

    /**
     * Store something in session temporarily.
     *
     * @param string $name
     * @param mixed $object
     * @return $this
     */
    public function setFlashObject($name, $object)
    {
        $this->__set($name, serialize($object));

        return $this;
    }

    /**
     * Return object and remove it from session.
     *
     * @param string $name
     * @return mixed
     */
    public function getFlashObject($name)
    {
        $serialized = $this->__get($name);

        $object = is_string($serialized) ? unserialize($serialized, ['allowed_classes' => true]) : $serialized;

        $this->__unset($name);

        if ($name === 'files-upload') {
            $grav = Grav::instance();

            // Make sure that Forms 3.0+ has been installed.
            if (null === $object && isset($grav['forms'])) {
                user_error(
                    __CLASS__ . '::' . __FUNCTION__ . '(\'files-upload\') is deprecated since Grav 1.6, use $form->getFlash()->getLegacyFiles() instead',
                    E_USER_DEPRECATED
                );

                /** @var Uri $uri */
                $uri = $grav['uri'];
                /** @var Forms|null $form */
                $form = $grav['forms']->getActiveForm(); // @phpstan-ignore-line

                $sessionField = base64_encode($uri->url);

                /** @var FormFlash|null $flash */
                $flash = $form ? $form->getFlash() : null; // @phpstan-ignore-line
                $object = $flash && method_exists($flash, 'getLegacyFiles') ? [$sessionField => $flash->getLegacyFiles()] : null;
            }
        }

        return $object;
    }

    /**
     * Store something in cookie temporarily.
     *
     * @param string $name
     * @param mixed $object
     * @param int $time
     * @return $this
     */
    public function setFlashCookieObject($name, $object, $time = 60)
    {
        setcookie($name, json_encode($object), time() + $time, '/', TRUE);

        return $this;
    }

    /**
     * Return object and remove it from the cookie.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getFlashCookieObject($name)
    {
        if (isset($_COOKIE[$name])) {
            $object = json_decode($_COOKIE[$name], false);
            setcookie($name, '', time() - 3600, '/');
            return $object;
        }

        return null;
    }

    /**
     * @return void
     */
    protected function onSessionStart(): void
    {
        $event = new SessionStartEvent($this);

        $grav = Grav::instance();
        $grav->dispatchEvent($event);
    }
}
