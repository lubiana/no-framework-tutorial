# Create a PHP application without a Framework

Hello and welcome to this tutorial with helps you in understanding how to write complex apps without the help of
a framework. This tutorial is not for people who have never written PHP before, you should at least have some
experience with object oriented PHP and be able to look at the official PHP-Documentation to figure out what
a function or class we are using does.

I often hear people talking about frameworks as a solution to all the problems that you have in software development.
But in my opinion its even worse to use a framework if you do not know what you are doing, because often are fighting
more against the framework than actually solving the problem you should be working on. Even if you know what you are
doing i think it is good to get to know how the frameworks you are using work under the hood and what challenges they
actually solve for you.

## Credit:

This tutorial is based on the great [tutorial by Patrick Louys](https://github.com/PatrickLouys/no-framework-tutorial).
My version is way more opiniated and uses some newer PHP features. But you should still check out his tutorial which is
still very great and helped me personally a lot in taking the next step in my knowledge about PHP development. There is
also an [amazing book](https://patricklouys.com/professional-php/) which expands on the topics covered in this tutorial.

## Getting started.

As I am using a fairly new version of PHP in this tutorial I have added a Vagrantfile to this tutorial. If you do not
have PHP8.1 installed on your computer you can use the following commands to try out all the examples:

```shell
vagrant up
vagrant ssh
cd app
```

I have exposed the port 1235 to be used in the VM, if you would like to use another one you are free to modify the
Vagrantfile.


[Start](01-front-controller.md)

