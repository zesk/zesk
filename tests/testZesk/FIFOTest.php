<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception\CommandFailed;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\SyntaxException;
use zesk\FIFO;
use zesk\PHP;
use zesk\Process;

/**
 * @coversDefaultClass \zesk\FIFO
 * @uses FIFO
 */
class FIFOTest extends TestCase
{
    /**
     * @return void
     * @covers ::__construct
     */
    public function test_fifo_create_false(): void
    {
        $fifo = new FIFO('does not matter whatever if create is false', false);
        $this->assertInstanceOf(FIFO::class, $fifo);
    }

    /**
     * @return void
     * @throws DirectoryNotFound
     * @throws FilePermission
     * @covers ::__construct
     */
    public function test_fifo_dnf(): void
    {
        $dir = $this->test_sandbox();

        $badFile = Directory::path($dir, 'notpath/notfind.pipe');

        $this->expectException(DirectoryNotFound::class);
        new FIFO($badFile, true);
    }

    /**
     * @return void
     * @covers ::__construct
     */
    public function test_fifo_permissions(): void
    {
        $this->expectException(FilePermission::class);
        new FIFO('/proc/sys/fail', true);
    }

    /**
     * @return void
     * @throws DirectoryNotFound
     * @throws FilePermission
     * @throws DirectoryCreate
     * @throws DirectoryPermission
     * $this->assertInstanceOf(FIFO::class, $fifo);
     * @covers ::__construct
     */
    public function test_fifo_permissions2(): void
    {
        $dir = $this->test_sandbox();

        $parentDir = Directory::path($dir, 'foo');
        Directory::depend($parentDir, 0o700);
        $badFile = Directory::path($parentDir, 'badperm.pipe');
        Directory::depend($badFile, 0o700);

        $this->expectException(FileNotFound::class);
        /* posix_mkfifo on a directory will fail I believe */
        new FIFO($badFile, true);
    }

    public static function data_fifo_dataSet(): array
    {
        return [
            [
                [
                    0,
                    1,
                    2,
                    3,
                    null,
                    'alphabet',
                    ['array'],
                    [
                        'a' => 1,
                        'b' => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $expected
     * @return void
     * @dataProvider data_fifo_dataSet
     * @return void
     * @throws DirectoryNotFound
     * @throws FilePermission
     * @throws CommandFailed
     * @throws FileNotFound
     * @throws SyntaxException
     * @covers ::read
     */
    public function test_fifo_reader(array $expected): void
    {
        $dir = $this->test_sandbox();

        $fifo_path = Directory::path($dir, 'reader.fifo');
        $fifo = new FIFO($fifo_path, true);

        $termToken = $this->randomHex(20);

        $map = ['path' => PHP::dump($fifo_path)];
        $writerCode = ['<?php'];
        $writerCode[] = '$f = new zesk\FIFO({path}, false);';
        foreach ($expected as $item) {
            $writerCode[] = '$f->write(' . PHP::dump($item) . ');';
        }
        $writerCode[] = '$f->write(' . PHP::dump($termToken) . ');';
        $writerCode[] = 'return 0;';
        $writerScript = ArrayTools::map(implode("\n", $writerCode), $map);
        $writerScriptPath = Directory::path($dir, 'writer.php');
        file_put_contents($writerScriptPath, $writerScript);
        $this->zeskEvalFileProcess($writerScriptPath);

        $results = [];
        do {
            $item = $fifo->read(100);
            if ($item === $termToken) {
                break;
            }
            $results[] = $item;
        } while (true);
        $this->assertEquals($expected, $results);
    }

    /**
     * @param array $expected
     * @return void
     * @throws CommandFailed
     * @throws DirectoryNotFound
     * @throws FileNotFound
     * @throws FilePermission
     * @throws SyntaxException
     * @dataProvider data_fifo_dataSet
     * @covers ::read
     * @covers ::write
     */
    public function test_fifo_writer(array $expected): void
    {
        $dir = $this->test_sandbox();

        $writer_path = Directory::path($dir, 'writer.fifo');
        $reader_path = Directory::path($dir, 'reader.fifo');
        $writer = new FIFO($writer_path, false);
        $reader = new FIFO($reader_path, true);

        $termToken = $this->randomHex(20);

        $map = [
            'writer_path' => PHP::dump($writer_path),
            'reader_path' => PHP::dump($reader_path),
        ];

        // Our process reads from the writer and writes to the reader
        $echoCode = ['<?php'];
        // $echoCode[] = 'echo "echo server started" . PHP_EOL;';
        $echoCode[] = 'fflush(STDOUT);';
        $echoCode[] = '$w = new zesk\FIFO({writer_path}, true);';
        $echoCode[] = '$r = new zesk\FIFO({reader_path}, false);';
        $echoCode[] = '$index = 0;';
        $echoCode[] = 'try { while (true) {';
        $echoCode[] = '    echo $index++ . PHP_EOL;';
        $echoCode[] = '    $r->write($w->read(10));';
        $echoCode[] = '} } catch (Exception) {}';
        $echoCode[] = 'echo "echo server terminated" . PHP_EOL;';
        $echoCode[] = 'fflush(STDOUT);';
        $echoCode[] = 'return 0;';
        $script = ArrayTools::map(implode("\n", $echoCode), $map);
        $scriptPath = Directory::path($dir, 'echo.php');
        $this->streamCapture(STDOUT);
        file_put_contents($scriptPath, $script);
        $pid = $this->zeskEvalFileProcess($scriptPath);
        $this->assertGreaterThan(0, $pid, "echo.php pid $pid");
        $this->assertTrue(Process::alive($pid));
        $this->awaitFile($writer_path, 10);
        $this->awaitFile($reader_path, 10);

        foreach ($expected as $item) {
            $writer->write($item);
            $readItem = $reader->read(10);
            $this->assertEquals($readItem, $item);
            if ($item === $termToken) {
                break;
            }
        }
        Process::term($pid);
    }
}
