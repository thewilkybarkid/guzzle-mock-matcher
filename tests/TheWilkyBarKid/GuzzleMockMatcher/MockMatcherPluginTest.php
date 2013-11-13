<?php

namespace TheWilkyBarKid\GuzzleMockMatcher;

use PHPUnit_Framework_TestCase as TestCase;

class MockMatcherPluginTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', new MockMatcherPlugin());
        $this->assertArrayHasKey('request.before_send', MockMatcherPlugin::getSubscribedEvents());
    }

    public function testUsesConstructorMatchers()
    {
        $matcher1 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher1->expects($this->once())->method('match')->will($this->returnValue(null));
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();
        $matcher2 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher2->expects($this->once())->method('match')->will($this->returnValue($response));

        $plugin = new MockMatcherPlugin(array($matcher1, $matcher2));

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $event = $this->getMock('Guzzle\Common\Event');
        $event->expects($this->any())->method('offsetGet')->with('request')->will($this->returnValue($request));

        $request->expects($this->once())->method('setResponse')->with($response);

        $plugin->onRequestBeforeSend($event);
    }

    public function testUsesAddedMatchers()
    {
        $matcher1 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher1->expects($this->once())->method('match')->will($this->returnValue(null));

        $plugin = new MockMatcherPlugin(array($matcher1));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();
        $matcher2 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher2->expects($this->once())->method('match')->will($this->returnValue($response));

        $this->assertSame($plugin, $plugin->addMatcher($matcher2));

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $event = $this->getMock('Guzzle\Common\Event');
        $event->expects($this->any())->method('offsetGet')->with('request')->will($this->returnValue($request));

        $request->expects($this->once())->method('setResponse')->with($response);

        $plugin->onRequestBeforeSend($event);
    }

    /**
     * @expectedException \TheWilkyBarKid\GuzzleMockMatcher\Exception\UnmatchedRequestException
     */
    public function testThrowsExceptionWhenARequestIsNotMatched()
    {
        $matcher1 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher1->expects($this->once())->method('match')->will($this->returnValue(null));
        $matcher2 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher2->expects($this->once())->method('match')->will($this->returnValue(null));

        $plugin = new MockMatcherPlugin(array($matcher1, $matcher2));

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $request->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
        $request->expects($this->any())->method('getUrl')->will($this->returnValue('http://localhost/'));
        $event = $this->getMock('Guzzle\Common\Event');
        $event->expects($this->any())->method('offsetGet')->with('request')->will($this->returnValue($request));

        $request->expects($this->never())->method('setResponse');

        $plugin->onRequestBeforeSend($event);
    }

    public function testDoesNothingIfAResponseIsAlreadySet()
    {
        $matcher1 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher1->expects($this->never())->method('match');
        $matcher2 = $this->getMock('TheWilkyBarKid\GuzzleMockMatcher\RequestMatcher\RequestMatcherInterface');
        $matcher2->expects($this->never())->method('match');

        $unmatchedRequestStrategy = $this->getMock(
            'TheWilkyBarKid\GuzzleMockMatcher\UnmatchedRequestStrategy\UnmatchedRequestStrategyInterface'
        );

        $plugin = new MockMatcherPlugin(array($matcher1, $matcher2), $unmatchedRequestStrategy);

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $request->expects($this->any())->method('getResponse')->will($this->returnValue($response));
        $event = $this->getMock('Guzzle\Common\Event');
        $event->expects($this->any())->method('offsetGet')->with('request')->will($this->returnValue($request));

        $request->expects($this->never())->method('setResponse');

        $unmatchedRequestStrategy->expects($this->never())->method('handle');

        $plugin->onRequestBeforeSend($event);
    }
}
