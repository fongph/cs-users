<?php

namespace CS\Users;

class FreeTrialLinks
{
    protected $domain;

    const HIDDEN = 0;
    const AVAILABLE = 1;
    const COOKIE_NAME = 'free_trial_links';

    public function __construct($cookieDomain)
    {
        $this->domain = $cookieDomain;
    }

    public function isAvailable()
    {
        return (bool) @ $_COOKIE[self::COOKIE_NAME];
    }

    public function setAccessCookie($isAvailable)
    {
        if ($isAvailable) {
            $_COOKIE[self::COOKIE_NAME] = self::AVAILABLE;
            setcookie(self::COOKIE_NAME, self::AVAILABLE, time()+60*60*24, '/', $this->domain);
        } else {
            unset($_COOKIE[self::COOKIE_NAME]);
            setcookie(self::COOKIE_NAME, self::HIDDEN, 1, '/', $this->domain);
        }
    }
}