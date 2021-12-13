<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\DriverException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Response;

class ErrorHandlingTest extends TestCase
{
    private TestClient $client;

    protected function setUp(): void
    {
        $this->client = new TestClient();
    }

    public function testGetClient(): void
    {
        $this->assertSame($this->client, $this->getDriver()->getClient());
    }

    /**
     * Looks like we have to mark these tests as "legacy", otherwise we get deprecation errors.
     * Although the deprecations are handled, there's no way to avoid the deprecation message here.
     * @group legacy
     */
    public function testGetResponseHeaderWithoutVisit(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unable to access the response before visiting a page');

        $this->getDriver()->getResponseHeaders();
    }

    /**
     * Looks like we have to mark these tests as "legacy", otherwise we get deprecation errors.
     * Although the deprecations are handled, there's no way to avoid the deprecation message here.
     * @group legacy
     */
    public function testFindWithoutVisit(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unable to access the response content before visiting a page');

        $this->getDriver()->find('//html');
    }

    /**
     * Looks like we have to mark these tests as "legacy", otherwise we get deprecation errors.
     * Although the deprecations are handled, there's no way to avoid the deprecation message here.
     * @group legacy
     */
    public function testGetCurrentUrlWithoutVisit(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unable to access the request before visiting a page');

        $this->getDriver()->getCurrentUrl();
    }

    public function testNotMatchingHtml5FormId(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('The selected node has an invalid form attribute (foo)');

        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input name="test" value="foo" form="foo">
        <input type="submit">
    </form>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    public function testInvalidHtml5FormId()
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('The selected node has an invalid form attribute (foo)');

        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input name="test" value="foo" form="foo">
        <input type="submit">
    </form>
    <div id="foo"></div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    public function testManipulateInputWithoutForm(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('The selected node does not have a form ancestor.');

        $html = <<<'HTML'
<html>
<body>
    <form id="test">
        <input type="submit">
    </form>
    <div id="foo">
        <input name="test" value="foo">
    </div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->setValue('//input[./@name="test"]', 'bar');
    }

    public function testClickOnUnsupportedElement(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Behat\Mink\Driver\BrowserKitDriver supports clicking on links and submit or reset buttons only. But "div" provided');

        $html = <<<'HTML'
<html>
<body>
    <div></div>
</body>
</html>
HTML;

        $this->client->setNextResponse(new Response($html));

        $driver = $this->getDriver();
        $driver->visit('/index.php');
        $driver->click('//div');
    }

    private function getDriver(): BrowserKitDriver
    {
        return new BrowserKitDriver($this->client);
    }
}

class TestClient extends AbstractBrowser
{
    protected $nextResponse = null;
    protected $nextScript = null;

    public function setNextResponse(Response $response): void
    {
        $this->nextResponse = $response;
    }

    public function setNextScript($script): void
    {
        $this->nextScript = $script;
    }

    protected function doRequest($request): Response
    {
        if (null === $this->nextResponse) {
            return new Response();
        }

        $response = $this->nextResponse;
        $this->nextResponse = null;

        return $response;
    }
}
