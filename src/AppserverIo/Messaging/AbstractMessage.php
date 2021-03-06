<?php

/**
 * AppserverIo\Messaging\AbstractMessage
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/messaging
 * @link      http://www.appserver.io
 */

namespace AppserverIo\Messaging;

use AppserverIo\Psr\Pms\QueueInterface;
use AppserverIo\Psr\Pms\MessageInterface;
use AppserverIo\Psr\Pms\MonitorInterface;
use AppserverIo\Psr\Pms\StateKeyInterface;
use AppserverIo\Psr\Pms\PriorityKeyInterface;
use AppserverIo\Messaging\Utils\PriorityKeys;
use AppserverIo\Messaging\Utils\PriorityLow;
use AppserverIo\Messaging\Utils\StateKeys;
use AppserverIo\Messaging\Utils\StateActive;
use AppserverIo\Messaging\Utils\StateFailed;
use AppserverIo\Messaging\Utils\StateProcessed;
use AppserverIo\Messaging\Utils\StatePaused;
use AppserverIo\Lang\Reflection\ReflectionObject;

/**
 * The abstract superclass for all messages.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/messaging
 * @link      http://www.appserver.io
 */
abstract class AbstractMessage implements MessageInterface, \Serializable
{

    /**
     * The unique session-ID.
     *
     * @var string
     */
    protected $sessionId = null;

    /**
     * The destination queue to send the message to.
     *
     * @var \AppserverIo\Psr\Pms\QueueInterface
     */
    protected $destination = null;

    /**
     * The parent message.
     *
     * @var \AppserverIo\Psr\Pms\MessageInterface
     */
    protected $parentMessage = null;

    /**
     * The monitor for monitoring the message.
     *
     * @var \AppserverIo\Psr\Pms\MonitorInterface
     */
    protected $messageMonitor = null;

    /**
     * The priority of the message, defaults to 'low'.
     *
     * @var integer
     */
    protected $priority = null;

    /**
     * The state of the message, defaults to 'active'.
     *
     * @var integer
     */
    protected $state = null;

    /**
     * The flag if the message should be deleted when finished or not.
     *
     * @var boolean
     */
    protected $locked = null;

    /**
     * The array with the timeouts for the retries.
     *
     * @var array
     */
    protected $retryTimeouts = array();

    /**
     * The array with the callbacks.
     *
     * @var array
     */
    protected $callbacks = array();

    /**
     * Initializes the message with the array
     * to send to the queue.
     */
    public function __construct()
    {
        // initialize the default values
        $this->sessionId = "";
        $this->priority = PriorityLow::KEY;
        $this->state = StateActive::KEY;
        $this->locked = false;
    }

    /**
     * Sets the unique session id.
     *
     * @param string $sessionId The uniquid id
     *
     * @return void
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Returns the unique session id.
     *
     * @return string The uniquid id
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Sets the destination queue.
     *
     * @param \AppserverIo\Psr\Pms\QueueInterface $destination The destination queue
     *
     * @return void
     */
    public function setDestination(QueueInterface $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Returns the destination queue.
     *
     * @return \AppserverIo\Psr\Pms\QueueInterface The destination queue
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Sets the priority of the message.
     *
     * @param \AppserverIo\Psr\Pms\PriorityKeyInterface $priority The priority to set the message to
     *
     * @return void
     */
    public function setPriority(PriorityKeyInterface $priority)
    {
        $this->priority = $priority->getPriority();
    }

    /**
     * Returns the priority of the message.
     *
     * @return \AppserverIo\Psr\Pms\PriorityKeyInterface The priority of the message
     */
    public function getPriority()
    {
        return PriorityKeys::get($this->priority);
    }

    /**
     * Sets the state of the message.
     *
     * @param \AppserverIo\Psr\Pms\StateKeyInterface $state The new state
     *
     * @return void
     */
    public function setState(StateKeyInterface $state)
    {
        $this->state = $state->getState();
    }

    /**
     * Returns the state of the message.
     *
     * @return \AppserverIo\Psr\Pms\StateKeyInterface The message state
     */
    public function getState()
    {
        return StateKeys::get($this->state);
    }

    /**
     * Sets the parent message.
     *
     * @param \AppserverIo\Psr\Pms\MessageInterface $parentMessage The parent message
     *
     * @return void
     */
    public function setParentMessage(MessageInterface $parentMessage)
    {
        $this->parentMessage = $parentMessage;
    }

    /**
     * Returns the parent message.
     *
     * @return \AppserverIo\Psr\Pms\MessageInterface The parent message
     *
     * @see \AppserverIo\Psr\Pms\MessageInterface::getParentMessage()
     */
    public function getParentMessage()
    {
        return $this->parentMessage;
    }

    /**
     * Sets the monitor for monitoring the message itself.
     *
     * @param \AppserverIo\Psr\Pms\MonitorInterface $messageMonitor The monitor
     *
     * @return void
     */
    public function setMessageMonitor(MonitorInterface $messageMonitor)
    {
        $this->messageMonitor = $messageMonitor;
    }

    /**
     * Returns the message monitor.
     *
     * @return \AppserverIo\Psr\Pms\MonitorInterface The monitor
     */
    public function getMessageMonitor()
    {
        return $this->messageMonitor;
    }

    /**
     * Locks the message.
     *
     * @return void
     */
    public function lock()
    {
        $this->locked = true;
    }

    /**
     * Unlocks the message.
     *
     * @return void
     */
    public function unlock()
    {
        $this->locked = false;
    }

    /**
     * Returns the message lock flag.
     *
     * @return boolean TRUE if the message is locked, else FALSE
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * Serializes the message and returns the serialized representation.
      *
     * @return string the string representation of the object or null
     * @link http://php.net/manual/en/serializable.serialize.php
     */
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    /**
     * The serialized representation of the message.
     *
     * @param string $data The string representation of the object
     *
     * @return void
     * @link http://php.net/manual/en/serializable.unserialize.php
     */
    public function unserialize($data)
    {
        foreach (unserialize($data) as $propertyName => $propertyValue) {
            $this->$propertyName = $propertyValue;
        }
    }

    /**
     * Set's the array with the retry timeouts which is also responsible
     * for the the number of retries.
     *
     * @param array $retryTimeouts The number of retries with their timeouts
     *
     * @return void
     */
    public function setRetryTimeouts(array $retryTimeouts)
    {
        $this->retryTimeouts = $retryTimeouts;
    }

    /**
     * Return's the array with the retry timeouts.
     *
     * @return array The retry timeouts
     */
    public function getRetryTimeouts()
    {
        return $this->retryTimeouts;
    }

    /**
     * Return's the timeout for the given retry.
     *
     * @param integer $retry The retry to return the timeout for
     *
     * @return integer The timeout in seconds for the passed retry
     * @throws \InvalidArgumentException Is thrown if the timeout for the passed retry is NOT available
     */
    public function getRetryTimeout($retry)
    {

        // try to find the timeout for the passed retry
        if (isset($this->retryTimeouts[$retry])) {
            return $this->retryTimeouts[$retry];
        }

        // throw an exception if the timeout is NOT available
        throw new \InvalidArgumentException(sprintf('Can\t find timeout information for retry %d', $retry));
    }

    /**
     * Return's the number of retries for this message.
     *
     * @return integer The number of retries
     */
    public function getRetryCounter()
    {
        return sizeof($this->getRetryTimeouts());
    }

    /**
     * Add's the callback for the given state.
     *
     * @param \AppserverIo\Psr\Pms\StateKeyInterface $state    The state to register the callback for
     * @param array                                  $callback The array with the bean's lookup and method name that has to be invoked
     *
     * @return void
     * @throws \Exception Is thrown if the passed state doesn't support callbacks
     */
    public function addCallback(StateKeyInterface $state, array $callback)
    {

        // query whether or not the state supports callbacks
        if (in_array($state->getState(), array(StateFailed::KEY, StateProcessed::KEY, StatePaused::KEY))) {
            // initialize the array with the state callbacks, if not already done
            if (!isset($this->callbacks[$state->getState()])) {
                $this->callbacks[$state->getState()] = array();
            }

            // add the callback to the state
            array_push($this->callbacks[$state->getState()], $callback);

            // return immediately
            return;
        }

        // throw an exception, if the passed state doesn't support callbacks
        throw new \Exception(
            sprintf(
                'Callbacks for state %s is not supported, state has to be either one of StateFailed, StateProcessed or StatePaused',
                (new ReflectionObject($state))->getShortName()
            )
        );
    }

    /**
     * Return's the callback information for the given state.
     *
     * @param \AppserverIo\Psr\Pms\StateKeyInterface $state The state to register the callback for
     *
     * @return array The array with the callback information
     */
    public function getCallbacks(StateKeyInterface $state)
    {

        // initialize the array with the callbacks
        $callbacks = array();

        // try to find the callback for the passed state
        if (isset($this->callbacks[$state->getState()])) {
            $callbacks = $this->callbacks[$state->getState()];
        }

        // return the array with the callbacks
        return $callbacks;
    }

    /**
     * Return's whether or not callbacks for the passed state has been registered.
     *
     * @param \AppserverIo\Psr\Pms\StateKeyInterface $state The state to register the callback for
     *
     * @return boolean TRUE if callbacks has been registered, else FALSE
     */
    public function hasCallbacks(StateKeyInterface $state)
    {
        return isset($this->callbacks[$state->getState()]);
    }
}
