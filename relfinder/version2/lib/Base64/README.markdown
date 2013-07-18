as3base64
---------

The `Base64` class can encode `String` or `ByteArray` objects to Base 64 encoding and vice versa.


Download
--------

You can download the latest version of the `Base64` class from [http://dynamicflash.com/goodies/base64](http://dynamicflash.com/goodies/base64)


Adding as3base64 to your project
--------------------------------

Before you can use the `Base64` class, you need to add the `as3base64.swc` file to your library path. 

If you're using Flex Builder 3, simple copy the file into your `libs` directory and it'll automatically be imported into your project. 

If you're using the Flex SDK command-line compiler you'll need to add the SWC to your library path using the `-library-path` command-line switch:
    
    mxmlc -library-path+=/path/to/as3base64.swc ...

Flash CS3 isn't smart enough to know what to to with a SWC file, so if you're using Flash CS3 you need to add the `src` directory to your classpath in your project's Publish Settings.

If you're one of the lucky people using Flash CS4, you just add the SWC to the classpath in your project's Publish Settings.


Usage
-----

Encoding from and decoding to `String`:

    import com.dynamicflash.util.Base64;

    var source:String = "Hello, world!";
    var encoded:String = Base64.encode(source);
    trace(encoded)

    var decoded:String = Base64.decode(source);
    trace(decoded);

Encoding from and decoding to a `ByteArray`:

    import com.dynamicflash.util.Base64;

    var obj:Object = {name:"Dynamic Flash", url:"http://dynamicflash.com"};
    var source:ByteArray = new ByteArray();
    source.writeObject(obj);
    var encoded:String = Base64.encodeByteArray(source);
    trace(encoded);

    var decoded:ByteArray = Base64.decodeToByteArray(encoded);
    var obj2:Object = decoded.readObject();
    trace(obj2.name + "(" + obj2.url + ")");


Compiling the SWC
-----------------

You need to make sure that the `compc` command-line compiler from the [Flex SDK]() is on your path. Then run either `build.sh` (Mac/Linux) or `build.bat` (Windows) from a terminal window to rebuild the `build/as3base64.swc` file. 


History
-------

## 1.1.0
Repackaged library as a SWC so that you don't have to have my code hanging around in your `src` folder.

## 1.0.0
Initial release.


Bug reports
-----------

Bug reports should be filed at [http://dynamicflash.com/goodies/base64](http://dynamicflash.com/goodies/base64)


License
-------

The base64 class is released under the MIT license.

Copyright (c) 2006 Steve Webster

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.