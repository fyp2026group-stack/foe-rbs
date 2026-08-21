const http = require('http');
const fs = require('fs');

const options = {
  hostname: 'localhost',
  port: 8000,
  path: '/api/login',
  method: 'OPTIONS',
  headers: {
    'Origin': 'http://localhost:5173',
    'Access-Control-Request-Method': 'POST'
  }
};

const req = http.request(options, (res) => {
  let body = '';
  res.on('data', (chunk) => { body += chunk; });
  res.on('end', () => {
    fs.writeFileSync('out.json', JSON.stringify({
      status: res.statusCode,
      headers: res.headers,
      body: body
    }, null, 2));
  });
});

req.on('error', (e) => {
  fs.writeFileSync('out.json', JSON.stringify({ error: e.message }));
});

req.end();
