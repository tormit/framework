<?php

use Mockery as m;
use Illuminate\View\Compilers\BladeCompiler;

class ViewBladeCompilerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(false);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotConstructWithBadCachePath()
    {
        new BladeCompiler($this->getFiles(), null);
    }

    public function testIsExpiredReturnsTrueWhenModificationTimesWarrant()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(true);
        $files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
        $files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(0);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testCompilePathIsProperlyCreated()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(__DIR__.'/'.sha1('foo').'.php', $compiler->getCompiledPath('foo'));
    }

    public function testCompileCompilesFileAndReturnsContents()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        $compiler->compile('foo');
    }

    public function testCompileCompilesAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        $compiler->compile('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileSetAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $compiler->setPath('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileWithPathSetBefore()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        // set path before compilation
        $compiler->setPath('foo');
        // trigger compilation with null $path
        $compiler->compile();
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testEchosAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!!$name!!}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!! $name !!}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!!
            $name
        !!}'));
        $this->assertEquals('<?php echo isset($name) ? $name : \'foo\'; ?>', $compiler->compileString('{!! $name or \'foo\' !!}'));

        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{$name}}}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{$name}}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{
            $name
        }}'));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $compiler->compileString("{{ \$name }}\r\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $compiler->compileString("{{ \$name }}\r\n"));

        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>', $compiler->compileString('{{ $name or "foo" }}'));
        $this->assertEquals('<?php echo e(isset($user->name) ? $user->name : "foo"); ?>', $compiler->compileString('{{ $user->name or "foo" }}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>', $compiler->compileString('{{$name or "foo"}}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>', $compiler->compileString('{{
            $name or "foo"
        }}'));

        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>', $compiler->compileString('{{ $name or \'foo\' }}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>', $compiler->compileString('{{$name or \'foo\'}}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>', $compiler->compileString('{{
            $name or \'foo\'
        }}'));

        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{ $age or 90 }}'));
        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{$age or 90}}'));
        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{
            $age or 90
        }}'));

        $this->assertEquals('<?php echo e("Hello world or foo"); ?>', $compiler->compileString('{{ "Hello world or foo" }}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>', $compiler->compileString('{{"Hello world or foo"}}'));
        $this->assertEquals('<?php echo e($foo + $or + $baz); ?>', $compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>', $compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>', $compiler->compileString('{{ \'Hello world or foo\' }}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>', $compiler->compileString('{{\'Hello world or foo\'}}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>', $compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertEquals('<?php echo e(myfunc(\'foo or bar\')); ?>', $compiler->compileString('{{ myfunc(\'foo or bar\') }}'));
        $this->assertEquals('<?php echo e(myfunc("foo or bar")); ?>', $compiler->compileString('{{ myfunc("foo or bar") }}'));
        $this->assertEquals('<?php echo e(myfunc("$name or \'foo\'")); ?>', $compiler->compileString('{{ myfunc("$name or \'foo\'") }}'));
    }

    public function testEscapedWithAtEchosAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('{{$name}}', $compiler->compileString('@{{$name}}'));
        $this->assertEquals('{{ $name }}', $compiler->compileString('@{{ $name }}'));
        $this->assertEquals('{{
            $name
        }}',
        $compiler->compileString('@{{
            $name
        }}'));
        $this->assertEquals('{{ $name }}
            ',
        $compiler->compileString('@{{ $name }}
            '));
    }

    public function testEscapedWithAtDirectivesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('@foreach', $compiler->compileString('@@foreach'));
        $this->assertEquals('@verbatim @continue @endverbatim', $compiler->compileString('@@verbatim @@continue @@endverbatim'));
        $this->assertEquals('@foreach($i as $x)', $compiler->compileString('@@foreach($i as $x)'));
        $this->assertEquals('@continue @break', $compiler->compileString('@@continue @@break'));
        $this->assertEquals('@foreach(
            $i as $x
        )', $compiler->compileString('@@foreach(
            $i as $x
        )'));
    }

    public function testExtendsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testPushIsCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@push(\'foo\')
test
@endpush';
        $expected = '<?php $__env->startPush(\'foo\'); ?>
test
<?php $__env->stopPush(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testStackIsCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@stack(\'foo\')';
        $expected = '<?php echo $__env->yieldPushContent(\'foo\'); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCommentsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '{{--this is a comment--}}';
        $expected = '<?php /*this is a comment*/ ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        $string = '{{--
this is a comment
--}}';
        $expected = '<?php /*
this is a comment
*/ ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        $string = sprintf('{{-- this is an %s long comment --}}', str_repeat('extremely ', 1000));
        $expected = sprintf('<?php /* this is an %s long comment */ ?>', str_repeat('extremely ', 1000));

        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@if (name(foo(bar)))
breeze
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testHasSectionStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@hasSection("section")
breeze
@endif';
        $expected = '<?php if (! empty(trim($__env->yieldContent("section")))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCanStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@can (\'update\', [$post])
breeze
@elsecan(\'delete\', [$post])
sneeze
@endcan';
        $expected = '<?php if (app(\'Illuminate\\Contracts\\Auth\\Access\\Gate\')->check(\'update\', [$post])): ?>
breeze
<?php elseif (app(\'Illuminate\\Contracts\\Auth\\Access\\Gate\')->check(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCannotStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@cannot (\'update\', [$post])
breeze
@elsecannot(\'delete\', [$post])
sneeze
@endcannot';
        $expected = '<?php if (app(\'Illuminate\\Contracts\\Auth\\Access\\Gate\')->denies(\'update\', [$post])): ?>
breeze
<?php elseif (app(\'Illuminate\\Contracts\\Auth\\Access\\Gate\')->denies(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testElseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@if (name(foo(bar)))
breeze
@else
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testElseIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@if(name(foo(bar)))
breeze
@elseif(boom(breeze))
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php elseif(boom(breeze)): ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testUnlessStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@unless (name(foo(bar)))
breeze
@endunless';
        $expected = '<?php if ( ! (name(foo(bar)))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testWhileStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@while ($foo)
test
@endwhile';
        $expected = '<?php while($foo): ?>
test
<?php endwhile; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedWhileStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@while ($foo)
@while ($bar)
test
@endwhile
@endwhile';
        $expected = '<?php while($foo): ?>
<?php while($bar): ?>
test
<?php endwhile; ?>
<?php endwhile; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testForStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsWithExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsWithExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedForStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
@for ($j = 0; $j < 20; $j++)
test
@endfor
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
<?php for($j = 0; $j < 20; $j++): ?>
test
<?php endfor; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testForeachStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@foreach ($this->getUsers() as $user)
test
@endforeach';
        $expected = '<?php foreach($this->getUsers() as $user): ?>
test
<?php endforeach; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedForeachStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@foreach ($this->getUsers() as $user)
user info
@foreach ($user->tags as $tag)
tag info
@endforeach
@endforeach';
        $expected = '<?php foreach($this->getUsers() as $user): ?>
user info
<?php foreach($user->tags as $tag): ?>
tag info
<?php endforeach; ?>
<?php endforeach; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testForelseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ($this->getUsers() as $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($this->getUsers() as $user): $__empty_1 = false; ?>
breeze
<?php endforeach; if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedForelseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ($this->getUsers() as $user)
@forelse ($user->tags as $tag)
breeze
@empty
tag empty
@endforelse
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($this->getUsers() as $user): $__empty_1 = false; ?>
<?php $__empty_2 = true; foreach($user->tags as $tag): $__empty_2 = false; ?>
breeze
<?php endforeach; if ($__empty_2): ?>
tag empty
<?php endif; ?>
<?php endforeach; if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testPhpStatementsWithExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@php($set = true)';
        $expected = '<?php ($set = true); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testPhpStatementsWithoutExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@php';
        $expected = '<?php ';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testEndphpStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@endphp';
        $expected = ' ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testUnsetStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@unset ($unset)';
        $expected = '<?php unset($unset); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testVerbatimBlocksAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@verbatim {{ $a }} @if($b) {{ $b }} @endif @endverbatim';
        $expected = ' {{ $a }} @if($b) {{ $b }} @endif ';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testVerbatimBlocksWithMultipleLinesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = 'Some text
@verbatim
    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
@endverbatim';
        $expected = 'Some text

    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testMultipleVerbatimBlocksAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@verbatim {{ $a }} @endverbatim {{ $b }} @verbatim {{ $c }} @endverbatim';
        $expected = ' {{ $a }}  <?php echo e($b); ?>  {{ $c }} ';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testStatementThatContainsNonConsecutiveParanthesisAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = "Foo @lang(function_call('foo(blah)')) bar";
        $expected = "Foo <?php echo app('translator')->get(function_call('foo(blah)')); ?> bar";
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testIncludesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(\'foo\')'));
        $this->assertEquals('<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(name(foo))'));
    }

    public function testIncludeIfsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php if ($__env->exists(\'foo\')) echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@includeIf(\'foo\')'));
        $this->assertEquals('<?php if ($__env->exists(name(foo))) echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@includeIf(name(foo))'));
    }

    public function testShowEachAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $compiler->compileString('@each(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->renderEach(name(foo)); ?>', $compiler->compileString('@each(name(foo))'));
    }

    public function testYieldsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\'); ?>', $compiler->compileString('@yield(\'foo\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\', \'bar\'); ?>', $compiler->compileString('@yield(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(name(foo)); ?>', $compiler->compileString('@yield(name(foo))'));
    }

    public function testShowsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldSection(); ?>', $compiler->compileString('@show'));
    }

    public function testLanguageAndChoicesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo app(\'translator\')->get(\'foo\'); ?>', $compiler->compileString("@lang('foo')"));
        $this->assertEquals('<?php echo app(\'translator\')->choice(\'foo\', 1); ?>', $compiler->compileString("@choice('foo', 1)"));
    }

    public function testSectionStartsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->startSection(\'foo\'); ?>', $compiler->compileString('@section(\'foo\')'));
        $this->assertEquals('<?php $__env->startSection(name(foo)); ?>', $compiler->compileString('@section(name(foo))'));
    }

    public function testStopSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->stopSection(); ?>', $compiler->compileString('@stop'));
    }

    public function testOverwriteSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->stopSection(true); ?>', $compiler->compileString('@overwrite'));
    }

    public function testEndSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->stopSection(); ?>', $compiler->compileString('@endsection'));
    }

    public function testAppendSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->appendSection(); ?>', $compiler->compileString('@append'));
    }

    public function testCustomPhpCodeIsCorrectlyHandled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php if($test): ?> <?php @show(\'test\'); ?> <?php endif; ?>', $compiler->compileString("@if(\$test) <?php @show('test'); ?> @endif"));
    }

    public function testMixingYieldAndEcho()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldContent(\'title\'); ?> - <?php echo e(Config::get(\'site.title\')); ?>', $compiler->compileString("@yield('title') - {{Config::get('site.title')}}"));
    }

    public function testCustomExtensionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->extend(function ($value) {
            return str_replace('foo', 'bar', $value);
        });
        $this->assertEquals('bar', $compiler->compileString('foo'));
    }

    public function testCustomStatements()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertCount(0, $compiler->getCustomDirectives());
        $compiler->directive('customControl', function ($expression) {
            return "<?php echo custom_control{$expression}; ?>";
        });
        $this->assertCount(1, $compiler->getCustomDirectives());

        $string = '@if($foo)
@customControl(10, $foo, \'bar\')
@endif';
        $expected = '<?php if($foo): ?>
<?php echo custom_control(10, $foo, \'bar\'); ?>
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCustomShortStatements()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->directive('customControl', function ($expression) {
            return '<?php echo custom_control(); ?>';
        });

        $string = '@customControl';
        $expected = '<?php echo custom_control(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCustomExtensionOverwritesCore()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->directive('foreach', function ($expression) {
            return '<?php custom(); ?>';
        });

        $string = '@foreach';
        $expected = '<?php custom(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testRawTagsCanBeSetToLegacyValues()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->setEchoFormat('%s');

        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{ $name }}}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{
            $name
        }}'));
    }

    public function testExpressionsOnTheSameLine()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo app(\'translator\')->get(foo(bar(baz(qux(breeze()))))); ?> space () <?php echo app(\'translator\')->get(foo(bar)); ?>', $compiler->compileString('@lang(foo(bar(baz(qux(breeze()))))) space () @lang(foo(bar))'));
    }

    public function testExpressionWithinHTML()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<html <?php echo e($foo); ?>>', $compiler->compileString('<html {{ $foo }}>'));
        $this->assertEquals('<html<?php echo e($foo); ?>>', $compiler->compileString('<html{{ $foo }}>'));
        $this->assertEquals('<html <?php echo e($foo); ?> <?php echo app(\'translator\')->get(\'foo\'); ?>>', $compiler->compileString('<html {{ $foo }} @lang(\'foo\')>'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }

    public function testRetrieveDefaultContentTags()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(['{{', '}}'], $compiler->getContentTags());
    }

    public function testRetrieveDefaultEscapedContentTags()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(['{{{', '}}}'], $compiler->getEscapedContentTags());
    }

    public function testSequentialCompileStringCalls()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testGetTagsProvider()
    {
        return [
            ['{{', '}}'],
            ['{{{', '}}}'],
            ['[[', ']]'],
            ['[[[', ']]]'],
            ['((', '))'],
            ['(((', ')))'],
        ];
    }
}
