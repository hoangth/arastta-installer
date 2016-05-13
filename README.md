# arastta-installer
An Installer for the Open Source Ecommerce package [Arastta](https://arastta.org/).

Inspired by the easy to use [laravel installer](https://github.com/laravel/installer), this package should make it easy to install fresh copies of Arastta.

It is advised to add the installer globally to composer:

    composer global require "mattythebatty/arastta-installer"
    
Once installed you should be able to create a new instance by typing

    arastta create store
    
into the console. By default this downloads the latest complied release from Arastta and places it into a folder called store.

For developers it will probably be more useful to use the **--latest** flag to download the project:

    arastta create store --latest
