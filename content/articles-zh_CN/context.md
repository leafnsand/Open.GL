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

`SDL_Init`函数根据一个位域作为参数表示需要加载那些模块。视频模块包含了创建窗体和OpenGL上下文所需的所有功能。

在做其他事情之前，先要告诉SDL你需要一个向前兼容的OpenGL 3.2上下文：

	SDL_GL_SetAttribute(SDL_GL_CONTEXT_PROFILE_MASK, SDL_GL_CONTEXT_PROFILE_CORE);
	SDL_GL_SetAttribute(SDL_GL_CONTEXT_MAJOR_VERSION, 3);
	SDL_GL_SetAttribute(SDL_GL_CONTEXT_MINOR_VERSION, 2);

然后再通过调用`SDL_CreateWindow`函数创建窗体。

	SDL_Window* window = SDL_CreateWindow("OpenGL", 100, 100, 800, 600, SDL_WINDOW_OPENGL);

第一个参数决定了窗体的标题，接着的两个参数是窗体的X，Y坐标，再接着的两个参数是窗体的宽和高。如果位置不重要，你可以传入`SDL_WINDOWPOS_UNDEFINED`或者`SDL_WINDOWPOS_CENTERED`作为第二个和第三个参数。最后一个参数指定了窗体的属性，例如：

- *SDL_WINDOW_OPENGL* - 创建一个OpenGL窗体
- *SDL_WINDOW_RESIZABLE* - 创建一个可调整大小的窗体
- **可选** *SDL_WINDOW_FULLSCREEN* - 创建一个全屏窗体

创建了窗体之后，你可以创建上下文：

	SDL_GLContext context = SDL_GL_CreateContext(window);
	...
	SDL_GL_DeleteContext(context);

上下文应该在调用`SDL_Quit()`之前调用以销毁资源。

接着是程序最重要的部分，事件循环：

	SDL_Event windowEvent;
	while (true)
	{
		if (SDL_PollEvent(&windowEvent))
		{
			if (windowEvent.type == SDL_QUIT) break;
		}

		SDL_GL_SwapWindow(window);
	}

`SDL_PollEvent`函数将会检查是否有新的事件需要处理。事件可能是鼠标点击或者用户移动窗体。现在，唯一需要你响应的时间是用户点击窗体角上的小红叉按钮。跳出了时间循环将会调用`SDL_Quit`，于是窗体和图形界面将会被销毁。`SDL_GL_SwapWindow`这里的作用是负责在应用程序绘制新的图形之后交换前后端缓冲区。

如果你是全屏窗体，应该优先选择`Esc`键作为关闭窗口的按键。

	if (windowEvent.type == SDL_KEYUP &&
		windowEvent.key.keysym.sym == SDLK_ESCAPE) break;

当你运行你的应用程序的时候，你应该会看到如下表现：

<img src="/media/img/c1_window.png" alt="" />

现在你已经有一个窗体和OpenGL上下文了，[还有一件事](#Onemorething)需要做。

GLFW
========

GLFW是专门为使用OpenGL定制的，所以它是迄今为止为了达到我们的目的最容易使用的。

构建
--------

当你下载了GLFW的二进制包或者你自己编译了源码之后，你需要的文件都在`lib`和`include`文件夹下。

- 把适当的`lib` 文件夹放入你的库路径下并且链接`GLFW`。
- 把`include`文件夹加入你的引用路径。

> 如果需要，你也可以动态链接GLFW库。只需要在预编译头中加入`GLFWDLL`并且把共享库文件放在你的可执行文件目录下。

为了确保你已经正确配置，试着编译运行下面几行代码：

	#include <GLFW/glfw3.h>
	#include <thread>

	int main()
	{
	    glfwInit();
		std::this_thread::sleep_for(std::chrono::seconds(1));
	    glfwTerminate();
	}

将会显示一个命令窗口并且在一秒后退出。如果你碰到了任何问题，请在下面留言，我将会替你答疑解惑。

编码
--------

我们从包含GLFW头文件和定义应用程序入口开始。

	#include <GLFW/glfw3.h>

	int main()
	{
		return 0;
	}

为了使用GLFW，它必须在程序开始的时候进行初始化，并且在程序退出前进行清理。`glfwInit`和`glfwTerminate`两个函数就是干这个用的。

	glfwInit();
	...
	glfwTerminate();

下一件事就是创建并且配置窗体。在调用`glfwCreateWindow`之前，我们先要设置几个参数。

	glfwWindowHint(GLFW_CONTEXT_VERSION_MAJOR, 3);
	glfwWindowHint(GLFW_CONTEXT_VERSION_MINOR, 2);
	glfwWindowHint(GLFW_OPENGL_PROFILE, GLFW_OPENGL_CORE_PROFILE);
	glfwWindowHint(GLFW_OPENGL_FORWARD_COMPAT, GL_TRUE);

	glfwWindowHint(GLFW_RESIZABLE, GL_FALSE);

	GLFWwindow* window = glfwCreateWindow(800, 600, "OpenGL", nullptr, nullptr); // 窗口
	GLFWwindow* window = glfwCreateWindow(800, 600, "OpenGL", glfwGetPrimaryMonitor(), nullptr); // 全屏

你会发现前三行代码是仅仅针对这个工具库的。它决定了我们需要OpenGL上下文至少支持OpenGL 3.2。`GLFW_OPENGL_PROFILE`选项决定了我们需要一个支持新的核心函数的上下文。

`glfwCreateWindow`的前两个参数决定了绘制层的宽和高，第三个参数决定了窗体的标题。窗口模式下第四个参数应该设置成`nullptr`，全屏模式下设置成`glfwGetPrimaryMonitor()`。最后一个参数允许你传入一个已经存在的OpenGL上下文来共享比如纹理之类的资源。`glfwWindowHint`函数是用来定义一些额外的窗体需求。

在创建好窗体后，OpenGL上下文应该这样设置为活动的：

	glfwMakeContextCurrent(window);

接下来就是事件循环，同时也是GLFW于其他工具库工作方式有所不同的地方。GLFW使用一个被称作*闭合*的事件循环，这意味着你只需要在必要的时候处理事件。也就是说你的事件循环看起来是如此简单的：

	while(!glfwWindowShouldClose(window))
	{
		glfwSwapBuffers(window);
		glfwPollEvents();
	}

循环中仅需要调用函数`glfwSwapBuffers`，用来在完成绘制后交换前后端缓冲区，以及`glfwPollEvents`函数用来恢复窗口事件。如果你在写一个全屏显示的应用程序，你应该处理`Esc`键的按下来返回桌面。

	if (glfwGetKey(window, GLFW_KEY_ESCAPE) == GLFW_PRESS)
		glfwSetWindowShouldClose(window, GL_TRUE);

如果你想学习更多的关于处理输入的功能，你可以参考[文档](http://www.glfw.org/docs/3.0/group__input.html).

当你运行你的应用程序的时候，你应该会看到如下表现：

<img src="/media/img/c1_window.png" alt="" />

现在你已经有一个窗体或者一个全屏的视图和OpenGL上下文了，在你开始绘制图形之前[还有一件事](#Onemorething)需要做。

还有一件事
========

很不幸，我们还不能就这样调用函数。这是因为显卡厂商的职责是在他们的显卡支持的硬件基础上实现OpenGL的功能。你不会希望你的的程序只能在一个驱动版本的显卡上运行，所以我们还需要做一些聪明的事。

你的程序需要在运行时检查什么功能是可用的并且动态的链接它们。这是通过查找函数的地址，把地址跟函数指针绑定，然后调用它来实现的。它看起来就像这样：

	// 定义函数原型
	typedef void (*GENBUFFERS) (GLsizei, GLuint*);

	// 加载函数的地址并且与指针绑定
	GENBUFFERS glGenBuffers = (GENBUFFERS)wglGetProcAddress("glGenBuffers");
	// Linux:
	GENBUFFERS glGenBuffers = (GENBUFFERS)glXGetProcAddress((const GLubyte *) "glGenBuffers");
	// OSX:
	GENBUFFERS glGenBuffers = (GENBUFFERS)NSGLGetProcAddress("glGenBuffers");

	// 像普通函数一样调用
	Gluint buffer;
	glGenBuffers(1, &buffer);

首先，我肯定，被这一段代码吓到是很正常的。你可能暂时还不熟悉函数指针，但至少试着大致揣摩一下它在这个过程中是一个什么样的东西。你大概可以想象一下，在整个过程中定义函数原型和查找函数地址是非常繁琐的，到最后简直就是浪费时间。

好消息是，已经有工具库来为我们解决这些问题了。当今最流行并且维护的最好的库是*GLEW*，并且近期也没有任何理由去更换。尽管如此，还有一个替代品*GLEE*，在初始化和清理方面跟它几乎一样好用。

如果你现在还没有编译GLEW，那么赶紧动手吧。我们现在将把GLEW加入我们的项目。

* 我们从链接`lib`文件夹里的静态GLEW库开始。根据你的平台不同，可能是`glew32s.lib`或者是`GLEW`。
* 把`include`文件夹加入你的引用路径。

现在只需要在你的程序里引用头文件，但是记得在引用OpenGL头文件或者你用来创建窗体的工具库头文件之前引用

	#define GLEW_STATIC
	#include <GL/glew.h>

不要忘记定义`GLEW_STATIC`，无论是定义一个宏`GLEW_STATIC`或是直接在编译命令或者项目设置中加入`-DGLEW_STATIC`。

> 如果你希望动态链接GLEW，就不用定义宏，在Windows下需要链接`glew32s.lib`而不是`glew32.lib`。不要忘记把`glew32.dll`或者`libGLEW.so`放到你的可执行文件同目录下！

现在只需要在创建好窗体和OpenGL上下文之后调用`glewInit()`了。如果要强制GLEW使用现代OpenGL的方法来检测某个函数是否可用，那么`glewExperimental`这一行是必须的。

	glewExperimental = GL_TRUE;
	glewInit();

调用GLEW为你载入的`glGenBuffers`函数来确定你已经正确设置好你的项目！

	GLuint vertexBuffer;
	glGenBuffers(1, &vertexBuffer);

	printf("%u\n", vertexBuffer);

你的程序编译运行应该不会报错并且在命令行打印出数字`1`。如果你需要更多的关于GLEW的帮助，你可以去[这里](http://glew.sourceforge.net/install.html)或者在评论中提问。

现在你已经通过了所有的配置和初始化工作，我建议你复制一份你现在的项目，以免将来新建工程的时候又要重新写一遍这些模板代码。

现在，让我们开始[绘制图形](/drawing)吧!
