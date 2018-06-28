# Cache Provider

Cache Provider 是参考于 Varnish 架构中的缓存机制，不过仅支持 PHP 代码层面缓存功能。

原理:

Users -> HTTP Request -> Cache Middleware -> Logic

当如果 GET 请求首次进入的时候会先请求具体逻辑，然后对请求的 url 进行 hash，然后怼返回结果进行缓存，缓存的机制来源于框架的 `cache()`。

当再次请求相同url的时候，会直接命中缓存并返回。另外非 GET 的请求，会直接进行穿透，如果在高并发插入数据的情况下，建议结合队列的方式去减轻压力。

### Support

如果你在使用中遇到问题，请联系: [bboyjanhuang@gmail.com](mailto:bboyjanhuang@gmail.com). 微博: [编码侠](http://weibo.com/ecbboyjan)

## License MIT
