const http = require('http');

const host = process.env.HOST || '127.0.0.1';
const port = Number(process.env.PORT || 3000);
const webTarget = new URL(process.env.WEB_TARGET || 'http://127.0.0.1:8000');
const apiTarget = new URL(process.env.API_TARGET || 'http://127.0.0.1:8001');

function proxy(request, response, target) {
  const headers = { ...request.headers };
  headers.host = target.host;
  headers['x-forwarded-host'] = request.headers.host || '';
  headers['x-forwarded-proto'] = 'http';
  headers['x-forwarded-for'] = request.socket.remoteAddress || '';

  const upstream = http.request(
    {
      protocol: target.protocol,
      hostname: target.hostname,
      port: target.port,
      method: request.method,
      path: request.url,
      headers,
    },
    (upstreamResponse) => {
      const responseHeaders = { ...upstreamResponse.headers };
      delete responseHeaders.host;

      response.writeHead(upstreamResponse.statusCode || 502, responseHeaders);
      upstreamResponse.pipe(response);
    },
  );

  upstream.on('error', () => {
    response.writeHead(502, { 'content-type': 'application/json; charset=utf-8' });
    response.end(JSON.stringify({ message: 'Upstream indisponivel.' }));
  });

  request.pipe(upstream);
}

http
  .createServer((request, response) => {
    const target = request.url && request.url.startsWith('/api') ? apiTarget : webTarget;
    proxy(request, response, target);
  })
  .listen(port, host, () => {
    console.log(`Gabarito360 edge listening on http://${host}:${port}`);
  });
