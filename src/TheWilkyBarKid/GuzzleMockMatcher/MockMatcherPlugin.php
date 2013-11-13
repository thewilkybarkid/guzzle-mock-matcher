<?php

namespace TheWilkyBarKid\GuzzleMockMatcher;

use Guzzle\Common\Event;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TheWilkyBarKid\GuzzleMockMatcher\Exception\UnmatchedRequestException;
use TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface;

/**
 * Match requests to mock responses.
 */
class MockMatcherPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => array('onRequestBeforeSend', -256),
        );
    }

    /**
     * Request matchers.
     *
     * @var RequestMatcherInterface[]
     */
    protected $matchers = array();

    /**
     * Constructor.
     *
     * @param RequestMatcherInterface[] $matchers Request matchers
     */
    public function __construct(array $matchers = array())
    {
        foreach ($matchers as $matcher) {
            $this->addMatcher($matcher);
        }
    }

    /**
     * Add a request matcher.
     *
     * @param RequestMatcherInterface $matcher Request Matcher
     *
     * @return self Reference to the plugin
     */
    public function addMatcher(RequestMatcherInterface $matcher)
    {
        $this->matchers[] = $matcher;

        return $this;
    }

    /**
     * Match a request to a response.
     *
     * @param Event $event Request before send event
     *
     * @throws UnmatchedRequestException If a request is not matched to a response
     */
    public function onRequestBeforeSend(Event $event)
    {
        /** @var RequestInterface $request */
        $request = $event['request'];

        if (null !== $request->getResponse()) {
            return;
        }

        foreach ($this->matchers as $matcher) {
            if (null !== $match = $matcher->match($request)) {
                $request->setResponse($match);

                return;
            }
        }

        throw new UnmatchedRequestException(sprintf(
            'No matching response found for the request %s %s',
            $request->getMethod(),
            $request->getUrl()
        ));
    }
}
