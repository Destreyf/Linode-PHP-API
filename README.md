Linode-PHP-API
==============

An API CLIENT for Linode services written in PHP that reads the spec from api.spec in order to build a dynamic resource.


Usage
=====
There are two ways to use this Linode Class, first using the "execute" method.
```PHP
$linode = new Linode('your-token-here');
$servers = $linode->execute('linode.list');
var_dump($servers);
```
This directly requests the resources, and if you have to pass in arguments you can do it like so
```PHP
$linode = new Linode('your-token-here');
$data = array(
  'DomainID'  => 1234,
  'Type'      => 'A', // Can be NS, MX, A, AAAA, CNAME, TXT or SRV
  'Name'      => 'hostname-or-fqdn',
  'Target'    => '1.2.3.4', 
);
$result = $linode->execute('domain.resource.create',$data);
```
with that you could get a result similar to this
```JSON
{
   "ERRORARRAY":[],
   "ACTION":"domain.resource.create",
   "DATA":{
      "ResourceID":28537
   }
}
```

The second way to use the class is by the magic methods automatically created when the spec is looked up.
```PHP
$linode = new Linode('your-token-here');
$servers = $linode->linode_list();
var_dump($servers);
```

The way this works, is by changing the dots to underscores, so linode.list becomes linode_list, this allows it to be called as a method.

As an example using the domain resource as an example
```PHP
$linode = new Linode('your-token-here');
$result = $linode->domain_resource_create(1234,'A','hostname-or-fqdn','1.2.3.4');

var_dump($result); // Should be the same as the previous example
```

The way that this works is by taking the parameters in order based on the api.spec command, allowing the system to dynamically build the methods requried to perform operations.

If you are unsure of the parameters and their order, you can issue the following

```PHP
$linode = new Linode('your-token-here');
$linode->describe('domain.resource.create');
```

This will automatically echo out the command structure for the API function requested.


Why, One already exists
=======================
Its true that there's already a Linode API (found here https://github.com/krmdrms/linode/) and its pretty good, with the exception that it has multiple requirements, as well the API is not based on the api.spec command (as far as i can tell).  The API probably doesn't get changed too often, but the fact that we can use the spec to build a dynamically available set of API commands is worth gold in my opinion.

What's the Request::forge???
============================
I work with FuelPHP, and use their cURL methods, but for those that are using it outside of FuelPHP it should work wonders, its simply a placeholder to handle the needed curl functions.
