<?php

namespace Pathologic\Commerce\Promocodes;

/**
 * Trait Messages
 * @package Pathologic\Commerce\Promocodes
 */
trait Messages
{
    protected $messages = [];
    /**
     * @param array $messages
     * @return $this
     */
    public function addMessages (array $messages = [])
    {
        if (!empty($messages)) {
            foreach ($messages as $message) {
                if (is_scalar($message)) {
                    $this->messages[] = $message;
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages ()
    {
        return $this->messages;
    }

    /**
     *
     */
    public function resetMessages ()
    {
        $this->messages = [];

        return $this;
    }
}
