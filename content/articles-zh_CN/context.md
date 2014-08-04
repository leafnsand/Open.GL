窗体和OpenGL上下文
========

在你绘制任何东西之前，你都必须先初始化OpenGL。初始化是通过创建一个OpenGL上下文来完成的。而上下文本质上是一个状态机，它储存了所有与你的应用程序渲染相关的数据。当你的应用程序关闭时，OpenGL上下文也很销毁掉并且释放掉。

这其中有一个问题，事实上创建窗体和上下文并不是OpenGL标准的一部分。这意味着这个过程在不同的平台是截然不同的！但是我们使用OpenGL开发应用程序的目的却又在于保持可移植性，所以这是我们必须解决的一个问题。庆幸的是，网络上有许多工具库提炼了这个过程，让你可以针对所有库支持的平台只用维护一份代码。

然而网络上的这些工具库都各有优点和缺点，并且他们的共同点是，都有着同样的一个程序流程。从指定游戏窗体的一些属性（例如标题，大小）和OpenGL上下文的属性（例如[抗锯齿](http://en.wikipedia.org/wiki/Anti-aliasing)级别）开始。接着你的应用程序将会启动事件循环，它包含了一些需要在应用程序关闭前不断重复完成的很重要的任务。这些任务通常是处理新的窗体事件，例如鼠标点击，更新渲染状态，绘制图形等等。

这个程序流程类似下面的这段伪代码：

	#include <libraryheaders>

	int main()
	{
		createWindow(title, width, height);
		createOpenGLContext(settings);

		while (windowOpen)
		{
			while (event = newEvent())
				handleEvent(event);

			updateScene();

			drawGraphics();
			presentGraphics();
		}

		return 0;
	}

在某一帧的渲染过程中，渲染的结果将会存储在一个叫做*后端缓冲区*的不可见缓冲区中，用来保证用户只看到最终结果。`presentGraphics()`的调用将会把这些结果从不可见缓冲区中复制到叫做*前端缓冲*的可视窗体缓冲区中。只要应用程序是实时渲染的，无论它使用工具库还是平台相关的代码创建，都能归结成这样一个程序过程。

默认情况下，工具库都会创建一个支持传统功能的OpenGL上下文。但这对于我们来说是不幸的，因为我们对这些也许未来会被废弃的东西不感兴趣。可幸的是我们可以告诉显卡驱动我们的应用程序是为未来准备的，并且不依赖于旧的函数。但不幸的是，现在只有GLFW是可以这样声明的。这一点点缺点现在不会带来任何消极的后果，所以不要因为它而过于影响你选择你的工具库，但是一个所谓的core profile上下文的优点是，如果不小心调用了旧的函数会返回一个无效操作的错误来让程序继续下去。

为了使OpenGL支持窗体大小的可调整，我们需要做很多复杂的处理，例如资源要被重新载入，缓冲区需要被重新创建来适应新的窗体大小。所以为了学习过程中更加方便，我们将不会关心这些细节，我们现在只针对固定大小（全屏）的窗体。

安装
=====

新建一个OpenGL项目的第一件事就是动态链接OpenGL库。

- **Windows**: 把 `opengl32.lib` 加入到项目的链接输入
- **Linux**: 在编译命令中加入 `-lGL`
- **OS X**: 在编译命令中加入 `-framework OpenGL`

<blockquote class="important">确保你<strong>没有</strong>把<code>opengl32.dll</code>放入你的项目中。这个文件在Windows中已经包含了，并且不同的Windows版本是有很大不同的。如果包含了这个文件，可能会在其他电脑上造成不可知的问题。</blockquote>

剩下的步骤根据你选择的创建窗体和上下文的工具库的不同而决定。

工具库
========

有很多工具库可以创建一个窗体并且同时创建好OpenGL上下文。这些工具库并没有一个最好的，因为每个人的需求和理想不同。这里我将针对三个最常用的工具库来示范完成创建的步骤，同时你可以去他们各自的网站上查找更多的使用方法。所有这章以后的代码将会根据你选择的工具库的不同而有所差异。

[SFML](#SFML)
--------

SFML是一个跨平台的C++多媒体库，它提供了访问图形，输入，音频，网络和操作系统的方法。使用这个库的缺点是，它力图成为一个集所有于一身的解决方案。你几乎无法控制OpenGL上下文的创建，因为它被设计成需要编程者使用它自己的一套绘图函数。

[SDL](#SDL)
--------

SDL同样也是一个跨平台的多媒体库，但它是针对C的。这使得它对于C++程序员来说有些许难用，但是它是SFML的一个很好的替代品。它支持了一些更奇特的平台，最重要的是，它比SFML提供了更多的对于创建OpenGL上下文的控制。

[GLFW](#GLFW)
--------

GLFW，正如它名字所说，是一个专门为使用OpenGL而设计的C语言库。与SDL和SFML不同的是，它只包含了一些必需的功能：窗体和上下文的创建、输入的管理。它是这三个库中提供了最多的对于OpenGL上下文创建的控制的工具库。

其他
--------

还有一些其他的选择，比如[freeglut](http://freeglut.sourceforge.net/)和[OpenGLUT](http://openglut.sourceforge.net/)，但是我个人认为上述几个工具库在控制，易用和最重要的保持更新方面要更优。

SFML
========

在SFML中，OpenGL上下文是在创建一个新窗体时一起创建的，所以你所要做的就是创建一个新窗体。SFML同时也提供了一个图形包，但是因为我们将直接使用OpenGL，所以我们不需要它。

构建
--------

当你下载了SFML的二进制包或者你自己编译了源码之后，你需要的文件都在`lib`和`include`文件夹下。

- 把`lib` 文件夹放入你的库路径下并且链接`sfml-system`和`sfml-window`。如果你使用Visual Studio，链接`lib/vc2008`文件夹下的`sfml-system-s`和`sfml-window-s`作为替代。
- 把`include`文件夹加入你的引用路径。

> SFML针对不同的配置有一个简单的命名约定。如果你需要动态链接，只需要从名字里去掉`-s`，定义宏`SFML_DYNAMIC`然后复制共享库。如果你要跟调试符号一起使用二进制，还要另外在名字中加上`-d`。

为了确保你已经正确配置，试着编译运行下面几行代码：

	#include <SFML/System.hpp>

	int main()
	{
		sf::sleep(sf::seconds(1.f));
		return 0;
	}

将会显示一个命令窗口并且在一秒后退出。如果你碰到了任何问题，在SFML的网站上可以找到[Visual Studio](http://sfml-dev.org/tutorials/2.1/start-vc.php)，[Code::Blocks](http://sfml-dev.org/tutorials/2.1/start-cb.php) 和[gcc](http://sfml-dev.org/tutorials/2.1/start-linux.php)的相关详细信息。

代码
--------

我们从包含window包的头文件和定义应用程序入口开始。

	#include <SFML/Window.hpp>

	int main()
	{
		return 0;
	}

实例化`sf::Window`会创建新的窗体。基础的构造函数的参数是`sf::VideoMode`结构，窗体标题和窗体风格。`sf::VideoMode`定义了宽和高还有可选的窗体像素深度。最后，创建一个固定宽高的窗体需要用`Style::Resize|Style::Close`覆盖默认的窗体风格。也可以通过传入`Style::Fullscreen`来创建一个全屏窗体。

	sf::Window window(sf::VideoMode(800, 600), "OpenGL", sf::Style::Close);

在构造函数中你还可以通过`sf::WindowSettings`结构来定义抗锯齿级别以及深度和模板缓冲区的精确度。后面两个概念会在后面提到，你现在可以暂时不用关心。

当你运行的时候，你会发现应用程序在创建窗体之后就退出了。让我们加入事件循环来解决这个问题。

	bool running = true;
	while (running)
	{
		sf::Event windowEvent;
		while (window.pollEvent(windowEvent))
		{

		}
	}

当你的窗体发生一些变化时，一个事件会传入这个事件队列。事件的种类很多，包括窗体的大小变化，鼠标移动和键盘按键。哪些事件需要做特殊处理是取决于你的，但是为了让应用程序正常运行至少有一个事件需要处理。

	switch (windowEvent.type)
	{
	case sf::Event::Closed:
		running = false;
		break;
	}

当用户视图关闭这个窗体时，`Closed`事件会被触发，我们这样处理可以保证应用程序会退出。你可以尝试删除这一行，然后会发现无法正常关闭窗体了。如果你需要全屏窗体，你应该增加`Esc`键按下作为关闭窗体的功能：

	case sf::Event::KeyPressed:
		if (windowEvent.key.code == sf::Keyboard::Escape)
			running = false;
		break;

现在你已经创建好窗体并且对重要的事件做了处理了，也就是说你现在可以把东西显示在屏幕上了。在绘制了一些东西之后，你可以调用`window.display()`来交换前端缓冲区和后端缓冲区。

当你运行你的应用程序的时候，你应该会看到如下表现：

<img src="/media/img/c1_window.png" alt="" />

SFML允许你拥有多个窗口。如果你想要使用这个特效，别忘了调用`window.setActive()`来激活一个窗口来进行绘制操作。

现在你已经有一个窗体和OpenGL上下文了，[还有一件事](#Onemorething)需要做。

SDL
========

SDL有许多不同的模块，但是只是创建窗体和OpenGL上下文，我们只需要关心视频模块。它会接管所有我们需要的事情，让我们看看怎么使用它。

构建
--------

当你下载了SDL的二进制包或者你自己编译了源码之后，你需要的文件都在`lib`和`include`文件夹下。

- 把`lib` 文件夹放入你的库路径下并且链接`SDL2`和`SDL2main`。
- SDL使用动态链接，所以确保动态引用库（`SDL2.dll`，`SDL2.so`）和你的可执行文件在同一目录下。
- 把`include`文件夹加入你的引用路径。

为了确保你已经正确配置，试着编译运行下面几行代码：

	#include <SDL.h>

	int main(int argc, char *argv[])
	{
		SDL_Init(SDL_INIT_EVERYTHING);

		SDL_Delay(1000);

		SDL_Quit();
		return 0;
	}

将会显示一个命令窗口并且在一秒后退出。如果你碰到了任何问题，你可以在SDL的[页面](http://wiki.libsdl.org/FrontPage)上找的各个平台和编译器的详细信息。

编码
--------

我们从包含SDL头文件和定义应用程序入口开始。

	#include <SDL.h>
	#include <SDL_opengl.h>

	int main(int argc, char *argv[])
	{
		return 0;
	}

要在你的程序中使用SDL，你需要告诉SDL哪几个模块需要用到，以及什么时候需要移除掉他们。以下两行代码可以解决这个问题。

	SDL_Init(SDL_INIT_VIDEO);
	...
	SDL_Quit();
	return 0;

The `SDL_Init` function takes a bitfield with the modules to load. The video module includes everything you need to create a window and an OpenGL context.

Before doing anything else, first tell SDL that you want a forward compatible OpenGL 3.2 context:

	SDL_GL_SetAttribute(SDL_GL_CONTEXT_PROFILE_MASK, SDL_GL_CONTEXT_PROFILE_CORE);
	SDL_GL_SetAttribute(SDL_GL_CONTEXT_MAJOR_VERSION, 3);
	SDL_GL_SetAttribute(SDL_GL_CONTEXT_MINOR_VERSION, 2);

After that, create a window using the `SDL_CreateWindow` function.

	SDL_Window* window = SDL_CreateWindow("OpenGL", 100, 100, 800, 600, SDL_WINDOW_OPENGL);

The first argument specifies the title of the window, the next two are the X and Y position and the two after those are the width and height. If the position doesn't matter, you can specify `SDL_WINDOWPOS_UNDEFINED` or `SDL_WINDOWPOS_CENTERED` for the second and third argument. The final parameter specifies window properties like:

- *SDL_WINDOW_OPENGL* - Create a window ready for OpenGL.
- *SDL_WINDOW_RESIZABLE* - Create a resizable window.
- **Optional** *SDL_WINDOW_FULLSCREEN* - Create a fullscreen window.

After you've created the window, you can create the OpenGL context:

	SDL_GLContext context = SDL_GL_CreateContext(window);
	...
	SDL_GL_DeleteContext(context);

The context should be destroyed right before calling `SDL_Quit()` to clean up the resources.

Then comes the most important part of the program, the event loop:

	SDL_Event windowEvent;
	while (true)
	{
		if (SDL_PollEvent(&windowEvent))
		{
			if (windowEvent.type == SDL_QUIT) break;
		}

		SDL_GL_SwapWindow(window);
	}

The `SDL_PollEvent` function will check if there are any new events that have to be handled. An event can be anything from a mouse click to the user moving the window. Right now, the only event you need to respond to is the user pressing the little X button in the corner of the window. By breaking from the main loop, `SDL_Quit` is called and the window and graphics surface are destroyed. `SDL_GL_SwapWindow` here takes care of swapping the front and back buffer after new things have been drawn by your application.

If you have a fullscreen window, it would be preferable to use the escape key as a means to close the window.

	if (windowEvent.type == SDL_KEYUP &&
		windowEvent.key.keysym.sym == SDLK_ESCAPE) break;

When you run your application now, you should see something like this:

<img src="/media/img/c1_window.png" alt="" />

Now that you have a window and a context, there's [one more thing](#Onemorething) that needs to be done.

GLFW
========

GLFW is tailored specifically for using OpenGL, so it is by far the easiest to use for our purpose.

Building
--------

After you've downloaded the GLFW binaries package from the website or compiled the library yourself, you'll find the headers in the `include` folder and the libraries for your compiler in one of the `lib` folders.

- Add the appropriate `lib` folder to your library path and link with `GLFW`.
- Add the `include` folder to your include path.

> You can also dynamically link with GLFW if you want to. Simply link with `GLFWDLL` and include the shared library with your executable.

Here is a simple snippet of code to check your build configuration:

	#include <GLFW/glfw3.h>
	#include <thread>

	int main()
	{
	    glfwInit();
		std::this_thread::sleep_for(std::chrono::seconds(1));
	    glfwTerminate();
	}

It should show a console application and exit after a second. If you run into any trouble, just ask in the comments and you'll receive help.

Code
--------

Start by simply including the GLFW header and define the entry point of the application.

	#include <GLFW/glfw3.h>

	int main()
	{
		return 0;
	}

To use GLFW, it needs to be initialised when the program starts and you need to give it a chance to clean up when your program closes. The `glfwInit` and `glfwTerminate` functions are geared towards that purpose.

	glfwInit();
	...
	glfwTerminate();

The next thing to do is creating and configuring the window. Before calling `glfwCreateWindow`, we first set some options.

	glfwWindowHint(GLFW_CONTEXT_VERSION_MAJOR, 3);
	glfwWindowHint(GLFW_CONTEXT_VERSION_MINOR, 2);
	glfwWindowHint(GLFW_OPENGL_PROFILE, GLFW_OPENGL_CORE_PROFILE);
	glfwWindowHint(GLFW_OPENGL_FORWARD_COMPAT, GL_TRUE);

	glfwWindowHint(GLFW_RESIZABLE, GL_FALSE);

	GLFWwindow* window = glfwCreateWindow(800, 600, "OpenGL", nullptr, nullptr); // Windowed
	GLFWwindow* window = glfwCreateWindow(800, 600, "OpenGL", glfwGetPrimaryMonitor(), nullptr); // Fullscreen

You'll immediately notice the first three lines of code that are only relevant for this library. It is specified that we require the OpenGL context to support OpenGL 3.2 at the least. The `GLFW_OPENGL_PROFILE` option specifies that we want a context that only supports the new core functionality.

The first two parameters of glfwCreateWindow specify the width and height of the drawing surface and the third parameter specifies the window title. The fourth parameter should be set to `NULL` for windowed mode and `glfwGetPrimaryMonitor()` for fullscreen mode. The last parameter allows you to specify an existing OpenGL context to share resources like textures with. The `glfwWindowHint` function is used to specify additional requirements for a window.

After creating the window, the OpenGL context has to be made active:

	glfwMakeContextCurrent(window);

Next comes the event loop, which in the case of GLFW works a little differently than the other libraries. GLFW uses a so-called *closed* event loop, which means you only have to handle events when you need to. That means your event loop will look really simple:

	while(!glfwWindowShouldClose(window))
	{
		glfwSwapBuffers(window);
		glfwPollEvents();
	}

The only required functions in the loop are `glfwSwapBuffers` to swap the back buffer and front buffer after you've finished drawing and `glfwPollEvents` to retrieve window events. If you are making a fullscreen application, you should handle the escape key to easily return to the desktop.

	if (glfwGetKey(window, GLFW_KEY_ESCAPE) == GLFW_PRESS)
		glfwSetWindowShouldClose(window, GL_TRUE);

If you want to learn more about handling input, you can refer to the [documentation](http://www.glfw.org/docs/3.0/group__input.html).

<img src="/media/img/c1_window.png" alt="" />

You should now have a window or a full screen surface with an OpenGL context. Before you can start drawing stuff however, there's [one more thing](#Onemorething) that needs to be done.

One more thing
========

Unfortunately, we can't just call the functions we need yet. This is because it's the duty of the graphics card vendor to implement OpenGL functionality in their drivers based on what the graphics card supports. You wouldn't want your program to only be compatible with a single driver version and graphics card, so we'll have to do something clever.

Your program needs to check which functions are available at runtime and link with them dynamically. This is done by finding the addresses of the functions, assigning them to function pointers and calling them. That looks something like this:

	// Specify prototype of function
	typedef void (*GENBUFFERS) (GLsizei, GLuint*);

	// Load address of function and assign it to a function pointer
	GENBUFFERS glGenBuffers = (GENBUFFERS)wglGetProcAddress("glGenBuffers");
	// or Linux:
	GENBUFFERS glGenBuffers = (GENBUFFERS)glXGetProcAddress((const GLubyte *) "glGenBuffers");
	// or OSX:
	GENBUFFERS glGenBuffers = (GENBUFFERS)NSGLGetProcAddress("glGenBuffers");

	// Call function as normal
	Gluint buffer;
	glGenBuffers(1, &buffer);

Let me begin by asserting that it is perfectly normal to be scared by this snippet of code. You may not be familiar with the concept of function pointers yet, but at least try to roughly understand what is happening here. You can imagine that going through this process of defining prototypes and finding addresses of functions is very tedious and in the end nothing more than a complete waste of time.

The good news is that there are libraries that have solved this problem for us. The most popular and best maintained library right now is *GLEW* and there's no reason for that to change anytime soon. Nevertheless, the alternative library *GLEE* works almost completely the same save for the initialization and cleanup code.

If you haven't built GLEW yet, do so now. We'll now add GLEW to your project.

* Start by linking your project with the static GLEW library in the `lib` folder. This is either `glew32s.lib` or `GLEW` depending on your platform.
* Add the `include` folder to your include path.

Now just include the header in your program, but make sure that it is included before the OpenGL headers or the library you used to create your window.

	#define GLEW_STATIC
	#include <GL/glew.h>

Don't forget to define `GLEW_STATIC` either using this preprocessor directive or by adding the `-DGLEW_STATIC` directive to your compiler command-line parameters or project settings.

> If you prefer to dynamically link with GLEW, leave out the define and link with `glew32.lib` instead of `glew32s.lib` on Windows. Don't forget to include `glew32.dll` or `libGLEW.so` with your executable!

Now all that's left is calling `glewInit()` after the creation of your window and OpenGL context. The `glewExperimental` line is necessary to force GLEW to use a modern OpenGL method for checking if a function is available.

	glewExperimental = GL_TRUE;
	glewInit();

Make sure that you've set up your project correctly by calling the `glGenBuffers` function, which was loaded by GLEW for you!

	GLuint vertexBuffer;
	glGenBuffers(1, &vertexBuffer);

	printf("%u\n", vertexBuffer);

Your program should compile and run without issues and display the number `1` in your console. If you need more help with using GLEW, you can refer to the [website](http://glew.sourceforge.net/install.html) or ask in the comments.

Now that we're past all of the configuration and initialization work, I'd advise you to make a copy of your current project so that you won't have to write all of the boilerplate code again when starting a new project.

Now, let's get to [drawing things](/drawing)!
