<?php

/**
 * @see       https://github.com/laminas/laminas-captcha for the canonical source repository
 * @copyright https://github.com/laminas/laminas-captcha/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-captcha/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Captcha;

use ArrayObject;
use Laminas\Captcha\Figlet as FigletCaptcha;
use Laminas\Session\Container as SessionContainer;

/**
 * @group      Laminas_Captcha
 */
class FigletTest extends CommonWordTest
{
    protected $wordClass = 'Laminas\Captcha\Figlet';

    /**
     * @var FigletCaptcha
     */
    protected $captcha;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        if (isset($this->word)) {
            unset($this->word);
        }

        $this->captcha = new FigletCaptcha([
            'sessionClass' => 'LaminasTest\Captcha\TestAsset\SessionContainer'
        ]);
    }

    public function testTimeoutPopulatedByDefault()
    {
        $ttl = $this->captcha->getTimeout();
        $this->assertNotEmpty($ttl);
        $this->assertIsInt($ttl);
    }

    public function testCanSetTimeout()
    {
        $ttl = $this->captcha->getTimeout();
        $this->captcha->setTimeout(3600);
        $this->assertNotEquals($ttl, $this->captcha->getTimeout());
        $this->assertEquals(3600, $this->captcha->getTimeout());
    }

    public function testGenerateReturnsId()
    {
        $id = $this->captcha->generate();
        $this->assertNotEmpty($id);
        $this->assertIsString($id);
        $this->id = $id;
    }

    public function testGetWordReturnsWord()
    {
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertNotEmpty($word);
        $this->assertIsString($word);
        $this->assertEquals(8, strlen($word));
        $this->word = $word;
    }

    public function testGetWordLength()
    {
        $this->captcha->setWordLen(4);
        $this->captcha->generate();
        $word = $this->captcha->getWord();
        $this->assertIsString($word);
        $this->assertEquals(4, strlen($word));
        $this->word = $word;
    }

    public function testGenerateIsRandomised()
    {
        $id1 = $this->captcha->generate();
        $word1 = $this->captcha->getWord();
        $id2 = $this->captcha->generate();
        $word2 = $this->captcha->getWord();

        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($word1, $word2);
    }

    public function testGenerateInitializesSessionData()
    {
        $this->captcha->generate();
        $session = $this->captcha->getSession();
        $this->assertEquals($this->captcha->getTimeout(), $session->setExpirationSeconds);
        $this->assertEquals(1, $session->setExpirationHops);
        $this->assertEquals($this->captcha->getWord(), $session->word);
    }

    public function testWordValidates()
    {
        $this->captcha->generate();
        $input = ['id' => $this->captcha->getId() , 'input' => $this->captcha->getWord()];
        $this->assertTrue($this->captcha->isValid($input));
    }

    public function testMissingNotValid()
    {
        $this->captcha->generate();
        $this->assertFalse($this->captcha->isValid(''));
        $this->assertFalse($this->captcha->isValid([]));
        $input = ['input' => 'blah'];
        $this->assertFalse($this->captcha->isValid($input));
    }

    public function testWrongWordNotValid()
    {
        $this->captcha->generate();
        $input = ["id" => $this->captcha->getId(), "input" => "blah"];
        $this->assertFalse($this->captcha->isValid($input));
    }

    public function testUsesFigletCaptchaHelperByDefault()
    {
        $this->assertEquals('captcha/figlet', $this->captcha->getHelperName());
    }

    public function testCaptchaShouldBeConfigurableViaTraversableObject()
    {
        $options = [
            'name'         => 'foo',
            'sessionClass' => 'LaminasTest\Captcha\TestAsset\SessionContainer',
            'wordLen'      => 6,
            'timeout'      => 300,
        ];
        $config  = new ArrayObject($options);
        $captcha = new FigletCaptcha($config);
        $test = $captcha->getOptions();
        $this->assertEquals($options, $test);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testShouldAllowFigletsLargerThanFourteenCharacters()
    {
        $this->captcha->setName('foo')
                      ->setWordLen(14);
        $this->captcha->generate();
    }

    public function testShouldNotValidateEmptyInputAgainstEmptySession()
    {
        // Regression Test for Laminas-4245
        $this->captcha->setName('foo')
                      ->setWordLen(6)
                      ->setTimeout(300);
        $id = $this->captcha->generate();

        // Unset the generated word
        // we have to reset $this->captcha for that
        $this->captcha->getSession()->word = null;
        $this->setUp();
        $this->captcha->setName('foo')
                      ->setWordLen(6)
                      ->setTimeout(300);
        $empty = [$this->captcha->getName() => ['id' => $id, 'input' => '']];
        $this->assertEquals(false, $this->captcha->isValid(null, $empty));
    }

    /**
     * @group Laminas-5728
     */
    public function testSetSessionWorks()
    {
        if (headers_sent($file, $line)) {
            $this->markTestSkipped("Cannot use sessions because headers already sent");
        }
        $session = new SessionContainer('captcha');
        $this->captcha->setSession($session);
        $this->captcha->generate();
        $input = ["id" => $this->captcha->getId(), "input" => $this->captcha->getWord()];
        $this->assertTrue($this->captcha->isValid($input));
        $this->assertEquals($session->word, $this->captcha->getWord());
    }
}
