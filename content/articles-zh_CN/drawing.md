图形管道
========

学习OpenGL就决定了你必须自己动手处理所有的困难。也就是说你将在血与暗的深渊里挣扎，不过一旦你领会到了要领，你就会意识到处理底层(*the hard way*)并不是那么艰难。这章结尾的练习将会让你知道，在使用现代OpenGL处理渲染的过程中，你将会有多少的控制力。

*图形管道*这一章涵盖了从输入数据得到最终图像的所有步骤。我将会以下面这张图的流程解释这些步骤。

<img src="/media/img/c2_pipeline.png" alt="" />

最一开始是*顶点矩阵*，它们来自我们将要构建的图形的顶点，比如三角形。矩阵中的每个顶点的都会存储一种特定的属性，具体是使用哪一种属性是由你决定的。一般使用的属性是世界里的3D位置和纹理坐标。

*顶点着色器*是运行在你的显卡上的一小段程序，它会单独处理每个输入的顶点矩阵里的顶点。这是处理透视变换的地方，也就是用来把3D世界里的点显示在2D屏幕上的方式！同时也会把一些重要的属性例如颜色和纹理坐标通过管道往下传递。

在输入的顶点矩阵通过变换处理了之后，显卡将会形成三角形，线段或者其他的点。这些图形被称作*图元（primitives）*，因为他们是更复杂的图形的基础。还有一些其他的可选择的绘图方式，比如三角形带，系带。如果你需要创建的图形是每个下一个图元都连接到最后一个的，就像一条由几个线段组成的连续曲线，这种方式能减少你需要生成的矩阵的大小。

接下来的步骤，*几何着色器*，是完全可选的，并且在最近这个概念才被引入。不像顶点着色器，几何着色器可以输出比输入更多的数据。它在形状组装阶段把图元作为输入，它可以在把图元往下传递给后面管道之前处理图元，丢弃图元,甚至替换成其他图元。由于GPU和PC其他部分的通信相对较慢，这个阶段可以帮助你减少需要传递的数据。以像素游戏为例，你可以把包含了在世界中的位置，颜色和材质的矩阵作为输入传递给几何着色器来产生实际的数据集。

在最终的图形列表被产生并且转换成屏幕坐标了之后，光栅会把图形的可见部分转化成以像素为单位的*片段*。从顶点着色器或者几何着色器中得到的顶点属性将会被插入片段中作为输入并且交给片段着色器处理。正如你可以在图片中看到的，即使只指定了三个点，颜色也被均匀的插入到了片段中组成了这个三角形。

*片段着色器*处理每个单独的片段和插入在其中的属性并且输出最终的颜色。通常是通过用插入的纹理坐标顶点属性从纹理中取样或者直接简单的输出一个颜色来完成的。在更复杂的情况下，也有可能进行灯光和阴影或者特效的计算处理。着色器同时也有着丢弃某些片段的能力，这意味着这某些图形可以被处理成半透的。

最后，最终的结果将会通过将这些图形片段混合在一起而生成并且进行深度和模板测试。关于最后这两点你只需要知道这是允许你使用其他更多的规则来扔掉某些特定片段而让其他的通过的方式。例如，如果一个三角形被另一个三角形覆盖，较近的三角形的片段将会在屏幕覆盖显示。

现在你知道你的显卡是如何把顶点矩阵变换成屏幕上的一幅图片的了，让我们开始动手吧！

顶点输入
========

你要确定的第一件事就是，显卡需要什么样的数据来让你正确地绘制场景。正如上面所提到的，数据来自于顶点的属性。你可以想出各种各样的你想要的属性，但是这些都难免要在*世界位置*的基础上。不论你是在处理2D图形还是3D图形，这都是会决定物体和图形将最终在屏幕上什么位置的属性。

> **设备坐标**
>
> 当你的顶点矩阵被上面所述的管道处理的时候，他们的坐标将会被变换成*设备坐标*。设备屏幕的坐标的X和Y坐标都被映射到-1到1之间。
>
> <br /><span style="text-align: center; display: block"><img src="/media/img/c2_dc.png" alt="" style="display: inline" /> <img src="/media/img/c2_dc2.png" alt="" style="display: inline" /></span><br />
>
> 就想一个图形，中间的这个点的坐标是`(0,0)`并且y轴是中间以上为正。这个看起来不是很自然因为图形程序通常以左上角为`(0,0)`并且右下角的坐标为`(width,height)`，但是这是简化3D计算的最好方式并且能保持与分辨率无关。

上面的三角形由顺时针方向的三个顶点确定：`(0,0.5)`，`(0.5,-0.5)`和`(-0.5,-0.5)`。很明显这里顶点的唯一差别就是位置，所以这是我们所需要的唯一属性。由于我们是直接传递设备坐标的，而X，Y来表示坐标就足够了。

OpenGL要求你把所有的顶点通过一个数组传递，一开始看起来会比较困惑。为了理解数组的格式，让我们看看对于我们的三角形它会是什么样子。

	float vertices[] = {
		 0.0f,  0.5f, // 顶点 1 (X, Y)
		 0.5f, -0.5f, // 顶点 2 (X, Y)
		-0.5f, -0.5f  // 顶点 3 (X, Y)
	};

正如你看到的，数组就是所有顶点和顶点属性的列表集合。属性排列的顺序并不重要，只需要每个顶点都是一样的即可。顶点的顺序不一定是连续的（即，其中的形状所形成的顺序），但是这会要求我们提供额外的数据来确定元素缓冲的格式。这将会在本章的最后进行讨论，因为现在这只会把事情变得更复杂。

下一步是把这些顶点数据上传到显卡。这一步非常重要因为显卡的内存更快并且你不必每次渲染屏幕（大约60次每秒）的时候都上传一遍数据。

这是通过创建一个*顶点缓冲区*（Vertex Buffer Object，VBO）来完成的：

	GLuint vbo;
	glGenBuffers(1, &vbo); // 创建1个缓冲区

这段内存是由OpenGL管理的，所以你将得到一个作为内存引用的正整数而不是一个内存指针。`GLuint`是一个跨平台的`unsigned int`的替代，同样`GLint`是`int`的替代。你在激活VBO时将会用到这个整数，直到销毁掉你才不需要它。

在上传数据之前你需要调用`glBindBuffer`来让它激活：

	glBindBuffer(GL_ARRAY_BUFFER, vbo);

类似`GL_ARRAY_BUFFER`，还有其他的枚举值来创建其他类型的缓冲区，但是它们现在并不重要。这段声明创建了活动的`array buffer`。现在我们能复制顶点数据到缓冲区中了。

	glBufferData(GL_ARRAY_BUFFER, sizeof(vertices), vertices, GL_STATIC_DRAW);

注意到这个函数并不需要VBO的id，但是需要传入数据数组。第二个参数确定了字节数。最后一个参数很重要，它的值要根据顶点数据的用途来确定。下面我列出了几个跟渲染相关的值：

- `GL_STATIC_DRAW`：顶点数据只会上传一次并且绘制很多次（例如，世界）。
- `GL_DYNAMIC_DRAW`：顶点数据会时不时改变，但是会被绘制比改变次数更多的次数。
- `GL_STREAM_DRAW`：顶点数据在每次绘制时都会改变（例如，用户界面）。

不同的用途将会决定数据存储在显卡里的什么样的内存中以提高性能。例如，以`GL_STREAM_DRAW`为类型的VBOs会存储在内存中以允许快速写入而稍慢的绘制。

带有属性的顶点数据现在被复制到了显卡了，但是他们暂时还不能使用。还记得我们可以以任意的顺序拼装数组，并且可以加入任何我们需要的属性吗？所以现在我们需要告诉显卡应该要如何处理这些属性了。与此同时，你也将会看到现代OpenGL到底是怎样的灵活了。

着色器
========

前面我们讨论过，你的顶点数据将会经过三个阶段的着色器处理。在旧版本的OpenGL中每个着色器阶段都定义了严格的目的，你只能稍微控制顶点数据发生了什么以及是如何发生的。但在现代OpenGL中，是由我们来告诉显卡要怎么处理数据的。这也就是为什么我们可以决定每个应用程序需要什么样的顶点属性。你将必须要编写顶点和片段着色器来让屏幕上显示一些东西，几何着色器是可选的，而我们将在[后面](geometry)讨论。

着色器是用一种被称作GLSL（OpenGl Shading Language）的C风格的语言。OpenGL将会在运行时从源代码编译出你的程序，并且复制到显卡。每个版本的OpenGL都有它对应版本的着色器语言以带来某些特定的新特性支持，而我们将会使用GLSL 1.50。这个版本号相比我们使用的OpenGL 3.3可能能看起来有些老，但那是因为着色器语言只在OpenGL 2.0时发布了GLSL 1.10。从OpenGL 3.3开始，这个问题将会得到解决，而GLSL版本号将会跟OpenGL版本号同步。

顶点着色器
--------

顶点着色器是运行在显卡上的一段程序，它用来处理顶点数组里的每个顶点和它的属性。它的职责是输入最终的设备顶点坐标并且输出片段着色器需要的任何数据。这就是为什么3D变化应该在这里进行处理。片段着色器以来与一些类似颜色和纹理坐标的属性，而通常这些数据都是直接从输入复制到输出而不经过任何运算。

还记得我们的顶点坐标已经是设备坐标了，并且我们没有任何属性，所以顶点着色器将会很简短。

	#version 150

	in vec2 position;

	void main()
	{
		gl_Position = vec4(position, 0.0, 1.0);
	}

`#version`的预处理指令是用来说明接下来的这段代码是GLSL 1.50代码。接下来我们定义了只有一个属性，位置。除了常规的C类型，GLSL还定义了以`vec*`和`mat*`声明的内置的向量和矩阵类型。这些结构中的值都是`float`。`vec`后的数字确定了(x, y, z, w)中组件的个数，而`mat`后的数字定义了行/列的个数。因为位置属性只提供了X和Y坐标，所以`vec2`是最适用的。

> 你可以用相当灵巧的方式处理这些顶点类型。上面的例子在使用`vec2`设置`vec4`前两个值时使用一个捷径。下面这两行是相同的：
>
>     gl_Position = vec4(position, 0.0, 1.0);
>     gl_Position = vec4(position.x, position.y, 0.0, 1.0);
>
> 当你在处理颜色数据的时候，你也同样能获取到单独的`r`，`g`，`b`和`a`，类似于`x`，`y`，`z`和`w`。这没有任何区别并且更加清晰。

最后的顶点位置会赋值给`gl_Position`变量，因为位置需要图元组装和很多其他内置的处理。为了让程序正常运行，最后一个值`w`需要赋值为`1.0f`。除了这个，你可以自由的使用属性做任何处理，在这章我们将会看到在给三角形加入了颜色之后如何输出。

片段着色器
--------

顶点着色器的输出将会插入屏幕上所有被图元覆盖的像素。这些像素被称作片段，同时也是片段着色器处理的东西。类似顶点着色器，它也有一个强制性的输出值，最终的片段颜色。是由你来编写代码来通过顶点颜色，纹理坐标和任何其他从顶点着色器输入的数据来计算片段颜色的。

我们的三角形只有白色的像素，所以片段着色器只需要每次都简单的输出这个颜色：

	#version 150

	out vec4 outColor;

	void main()
	{
		outColor = vec4(1.0, 1.0, 1.0, 1.0);
	}

你肯定注意到了我们没有使用叫做`gl_FragColor`内置的变量来输入颜色。这是因为片段着色器实际上可以多个颜色，我们将会看到如何在加载这些着色器的时候处理这个。`outColor`变量是`vec4`类型的，因为每个颜色由红，绿，蓝和透明度组成。OpenGL中的颜色是用`0.0`到`1.0`之间的浮点数表示的，而不是`0`到`255`。

编译着色器
--------

只要你加载了源代码之后（无论是通过文件还是硬编码字符串），编译着色器就很简单了。类似顶点缓冲，它通过创建一个着色器元素得到，并且上传数据。

	GLuint vertexShader = glCreateShader(GL_VERTEX_SHADER);
	glShaderSource(vertexShader, 1, &vertexSource, NULL);

不同于VBOs，你可以简单的传入一个引用给着色器函数而不用激活它或者其他类似处理。`glShaderSource`可以接受一个由多个源码字符串组成的数组，但是通常你不会把源码放在一个`char`数组里。最后一个参数可以传入数组的长度，使用`NULL`会简单的以空字符串作为结尾。

现在只需要编译这段着色器以得到可以在显卡上运行的代码了：

	glCompileShader(vertexShader);

要注意如果着色器编译失败了，比如语法错误，`glGetError`**得不到**错误！下面这段代码可以检查着色器是否便以失败。

> **检查一段着色器代码是否编译正确**
>
>     GLint status;
>     glGetShaderiv(vertexShader, GL_COMPILE_STATUS, &status);
>
> 如果`status`等于`GL_TRUE`，那么你的着色器代码编译通过了。
> <br/><br/>
> **检索编译log**
>
>     char buffer[512];
>     glGetShaderInfoLog(vertexShader, 512, NULL, buffer);
>
> 这个函数会保存编译log的前511字节加上一个空结束字符到指定的缓存中。及时编译成功，这个log也会报告一些有用的警告信息，所以在你开发着色器的时候时不时检查一下log是很有用的。

片段着色器也是以同样的方式编译：

	GLuint fragmentShader = glCreateShader(GL_FRAGMENT_SHADER);
	glShaderSource(fragmentShader, 1, &fragmentSource, NULL);
	glCompileShader(fragmentShader);

再次，记得检查你的着色器是否编译正确，因为这会让你待会儿不会头疼。

着色器与程序结合
--------

到现在顶点着色器和片段着色器还是两个分开的部分。然而他们是要共同工作的，只是他们还没连接到一起。这个连接是通过创建一个着色器之外的*程序*来完成的。

	GLuint shaderProgram = glCreateProgram();
	glAttachShader(shaderProgram, vertexShader);
	glAttachShader(shaderProgram, fragmentShader);

由于一个片段着色器是可以被写入到多个缓冲中的，你需要之处哪一个输出是为哪一段缓冲的。这需要在链接程序之前完成。然后由于默认是0而且现在只有一个输出，所以下面这一行代码不是必须的：

	glBindFragDataLocation(shaderProgram, 0, "outColor");

在附着上顶点和片段着色器之后，连接是通过*链接*程序来完成的。在添加到一个（或多个）程序之后我们仍可以对着色器进行修改，但是实际的结果并不会改变只到重新链接程序。同样也可以针对同样的阶段添加多个着色器（例如，片段着色阶段），如果需要他们一起组成整个着色器。着色器可以通过`glDeleteShader`来删除，但是只有在在所有依附的程序上调用了`glDetachShader`才会真正被移除。

	glLinkProgram(shaderProgram);

要真正在程序中使用着色器，只需要这样：

	glUseProgram(shaderProgram);

类似顶点缓冲，同时只有一个程序能被激活。

在顶点数据和属性间建立链接
--------

虽然我们现在已经有了顶点数据和着色器了，但是OpenGL仍然不知道这些属性是什么样的格式已经是怎么排序的。你需要先检索输入`position`在顶点着色器中的引用。

	GLint posAttrib = glGetAttribLocation(shaderProgram, "position");

地址是一个与输入定义顺序相关的数字。在这个例子中，第一个并且是唯一一个的输入`position`将一直都是地址0。

有了输入的引用，你可以指定这个输入的数据要怎么从数组中索引了：

	glVertexAttribPointer(posAttrib, 2, GL_FLOAT, GL_FALSE, 0, 0);

第一个是输入的引用。第二个参数指定了这个输入需要的数的个数，也就是`vec`成员的个数。第三个参数指定了每个成员的类型，第四个参数指定了是否需要把输入的值归一化至`-1.0`和`1.0`之间（或者某些格式是`0.0`到`1.0`之间）如果它们不是浮点数。

最后两个参数可以说是这里最重要的，因为它们定义了属性在顶点数组中是怎么排列的。第一个数字指定了*步长（stride）*，也就是数组中每个位置属性间隔了多少位。0意味着中间没有数据。在目前的情况下每个顶点的位置都是紧跟着下一个顶点的位置的。最后一个参数指定了*偏移（offset）*，也就是从数组最开始经过多少字节才有参数出现。由于现在没有其他的参数，所以也是0。

It is important to know that this function will store not only the stride and the offset, but also the VBO that is currently bound to `GL_ARRAY_BUFFER`. That means that you don't have to explicitly bind the correct VBO when the actual drawing functions are called. This also implies that you can use a different VBO for each attribute.

Don't worry if you don't fully understand this yet, as we'll see how to alter this to add more attributes soon enough.

	glEnableVertexAttribArray(posAttrib);

Last, but not least, the vertex attribute array needs to be enabled.

Vertex Array Objects
--------

You can imagine that real graphics programs use many different shaders and vertex layouts to take care of a wide variety of needs and special effects. Changing the active shader program is easy enough with a call to `glUseProgram`, but it would be quite inconvenient if you had to set up all of the attributes again every time.

Luckily, OpenGL solves that problem with *Vertex Array Objects* (VAO). VAOs store all of the links between the attributes and your VBOs with raw vertex data.

A VAO is created in the same way as a VBO:

	GLuint vao;
	glGenVertexArrays(1, &vao);

To start using it, simply bind it:

	glBindVertexArray(vao);

As soon as you've bound a certain VAO, every time you call `glVertexAttribPointer`, that information will be stored in that VAO. This makes switching between different vertex data and vertex formats as easy as binding a different VAO! Just remember that a VAO doesn't store any vertex data by itself, it just references the VBOs you've created and how to retrieve the attribute values from them.

Since only calls after binding a VAO stick to it, make sure that you've created and bound the VAO at the start of your program.

Drawing
========

Now that you've loaded the vertex data, created the shader programs and linked the data to the attributes, you're ready to draw the triangle. The VAO that was used to store the attribute information is already bound, so you don't have to worry about that. All that's left is to simply call `glDrawArrays` in your main loop:

	glDrawArrays(GL_TRIANGLES, 0, 3);

The first parameter specifies the kind of primitive (commonly point, line or triangle), the second parameter specifies how many vertices to skip at the beginning and the last parameter specifies the number of **vertices** (not primitives!) to process.

When you run your program now, you should see the following:

<img src="/media/img/c2_window.png" alt="" />

If you don't see anything, make sure that the shaders have compiled correctly, that the program has linked correctly, that the attribute array has been enabled, that the VAO has been bound before specifying the attributes, that your vertex data is correct and that `glGetError` returns `0`. If you can't find the problem, try comparing your code to [this sample](/content/code/c2_triangle.txt).

Uniforms
========

Right now the white color of the triangle has been hard-coded into the shader code, but what if you wanted to change it after compiling the shader? As it turns out, vertex attributes are not the only way to pass data to shader programs. There is another way to pass data to the shaders called *uniforms*. These are essentially global variables, having the same value for all vertices and/or fragments. To demonstrate how to use these, let's make it possible to change the color of the triangle from the program itself.

By making the color in the fragment shader a uniform, it will end up looking like this:

	#version 150

	uniform vec3 triangleColor;

	out vec4 outColor;

	void main()
	{
		outColor = vec4(triangleColor, 1.0);
	}

The last component of the output color is transparency, which is not very interesting right now. If you run your program now you'll see that the triangle is black, because the value of `triangleColor` hasn't been set yet.

Changing the value of a uniform is just like setting vertex attributes, you first have to grab the location:

	GLint uniColor = glGetUniformLocation(shaderProgram, "triangleColor");

The values of uniforms are changed with any of the `glUniformXY` functions, where X is the number of components and Y is the type. Common types are `f` (float), `d` (double) and `i` (integer).

	glUniform3f(uniColor, 1.0f, 0.0f, 0.0f);

If you run your program now, you'll see that the triangle is red. To make things a little more exciting, try varying the color with the time by doing something like this in your main loop:

	float time = (float)clock() / (float)CLOCKS_PER_SEC;
	glUniform3f(uniColor, (sin(time * 4.0f) + 1.0f) / 2.0f, 0.0f, 0.0f);

Although this example may not be very exciting, it does demonstrate that uniforms are essential for controlling the behaviour of shaders at runtime. Vertex attributes on the other hand are ideal for describing a single vertex.

<div class="livedemo_wrap">
	<div class="livedemo" id="demo_c2_uniforms" style="background: url('/media/img/c2_window3.png')">
		<canvas width="640" height="480"></canvas>
		<script type="text/javascript" src="/content/demos/c2_uniforms.js"></script>
	</div>
</div>

See [the code](/content/code/c2_triangle_uniform.txt) if you have any trouble getting this to work.

Adding some more colors
========

Although uniforms have their place, color is something we'd rather like to specify per corner of the triangle! Let's add a color attribute to the vertices to accomplish this.

We'll first have to add the extra attributes to the vertex data. Transparency isn't really relevant, so we'll only add the red, green and blue components:

	float vertices[] = {
		 0.0f,  0.5f, 1.0f, 0.0f, 0.0f, // Vertex 1: Red
		 0.5f, -0.5f, 0.0f, 1.0f, 0.0f, // Vertex 2: Green
		-0.5f, -0.5f, 0.0f, 0.0f, 1.0f  // Vertex 3: Blue
	};

Then we have to change the vertex shader to take it as input and pass it to the fragment shader:

	#version 150

	in vec2 position;
	in vec3 color;

	out vec3 Color;

	void main()
	{
		Color = color;
		gl_Position = vec4(position, 0.0, 1.0);
	}

And `Color` is added as input to the fragment shader:

	#version 150

	in vec3 Color;

	out vec4 outColor;

	void main()
	{
		outColor = vec4(Color, 1.0);
	}

Make sure that the output of the vertex shader and the input of the fragment shader have the same name, or the shaders will not be linked properly.

Now, we just need to alter the attribute pointer code a bit to accommodate for the new `X, Y, R, G, B` attribute order.

	GLint posAttrib = glGetAttribLocation(shaderProgram, "position");
	glEnableVertexAttribArray(posAttrib);
	glVertexAttribPointer(posAttrib, 2, GL_FLOAT, GL_FALSE,
						   5*sizeof(float), 0);

	GLint colAttrib = glGetAttribLocation(shaderProgram, "color");
	glEnableVertexAttribArray(colAttrib);
	glVertexAttribPointer(colAttrib, 3, GL_FLOAT, GL_FALSE,
						   5*sizeof(float), (void*)(2*sizeof(float)));

The fifth parameter is set to `5*sizeof(float)` now, because each vertex consists of 5 floating point attribute values. The offset of `2*sizeof(float)` for the color attribute is there because each vertex starts with 2 floating point values for the position that it has to skip over.

And we're done!

<img src="/media/img/c2_window2.png" alt="" />

You should now have a reasonable understanding of vertex attributes and shaders. If you ran into problems, ask in the comments or have a look at the altered [source code](/content/code/c2_color_triangle.txt).

Element buffers
========

Right now, the vertices are specified in the order in which they are drawn. If you wanted to add another triangle, you would have to add 3 additional vertices to the vertex array. There is a way to control the order, which also enables you to reuse existing vertices. This can save you a lot of memory when working with real 3D models later on, because each point is usually occupied by a corner of three triangles!

An element array is filled with unsigned integers referring to vertices bound to `GL_ARRAY_BUFFER`. If we just want to draw them in the order they are in now, it'll look like this:

	GLuint elements[] = {
		0, 1, 2
	};

They are loaded into video memory through a VBO just like the vertex data:

	GLuint ebo;
	glGenBuffers(1, &ebo);

	...

	glBindBuffer(GL_ELEMENT_ARRAY_BUFFER, ebo);
	glBufferData(GL_ELEMENT_ARRAY_BUFFER,
		sizeof(elements), elements, GL_STATIC_DRAW);

The only thing that differs is the target, which is `GL_ELEMENT_ARRAY_BUFFER` this time.

To actually make use of this buffer, you'll have to change the draw command:

	glDrawElements(GL_TRIANGLES, 3, GL_UNSIGNED_INT, 0);

The first parameter is the same as with `glDrawArrays`, but the other ones all refer to the element buffer. The second parameter specifies the number of indices to draw, the third parameter specifies the type of the element data and the last parameter specifies the offset. The only real difference is that you're talking about indices instead of vertices now.

To see how an element buffer can be beneficial, let's try drawing a rectangle using two triangles. We'll start by doing it without an element buffer.

	float vertices[] = {
		-0.5f,  0.5f, 1.0f, 0.0f, 0.0f, // Top-left
		 0.5f,  0.5f, 0.0f, 1.0f, 0.0f, // Top-right
		 0.5f, -0.5f, 0.0f, 0.0f, 1.0f, // Bottom-right

		 0.5f, -0.5f, 0.0f, 0.0f, 1.0f, // Bottom-right
		-0.5f, -0.5f, 1.0f, 1.0f, 1.0f, // Bottom-left
		-0.5f,  0.5f, 1.0f, 0.0f, 0.0f  // Top-left
	};

By calling `glDrawArrays` instead of `glDrawElements` like before, the element buffer will simply be ignored:

	glDrawArrays(GL_TRIANGLES, 0, 6);

The rectangle is rendered as it should, but the repetition of vertex data is a waste of memory. Using an element buffer allows you to reuse data:

	float vertices[] = {
		-0.5f,  0.5f, 1.0f, 0.0f, 0.0f, // Top-left
		 0.5f,  0.5f, 0.0f, 1.0f, 0.0f, // Top-right
		 0.5f, -0.5f, 0.0f, 0.0f, 1.0f, // Bottom-right
		-0.5f, -0.5f, 1.0f, 1.0f, 1.0f  // Bottom-left
	};

	...

	GLuint elements[] = {
		0, 1, 2,
		2, 3, 0
	};

	...

	glDrawElements(GL_TRIANGLES, 6, GL_UNSIGNED_INT, 0);

The element buffer still specifies 6 vertices to form 2 triangles like before, but now we're able to reuse vertices! This may not seem like much of a big deal at this point, but when your graphics application loads many models into the relatively small graphics memory, element buffers will be an important area of optimization.

<img src="/media/img/c2_window4.png" alt="" />

If you run into trouble, have a look at the full [source code](/content/code/c2_triangle_elements.txt).

This chapter has covered all of the core principles of drawing things with OpenGL and it's absolutely essential that you have a good understanding of them before continuing. Therefore I advise you to do the exercises below before diving into [textures](/textures).

Exercises
========

- Alter the vertex shader so that the triangle is upside down. ([Solution](/content/code/c2_exercise_1.txt))
- Invert the colors of the triangle by altering the fragment shader. ([Solution](/content/code/c2_exercise_2.txt))
- Change the program so that each vertex has only one color value, determining the shade of gray. ([Solution](/content/code/c2_exercise_3.txt))
