import { createServer } from 'node:http';
import { extname, join, normalize, resolve, sep } from 'node:path';
import { readFile } from 'node:fs/promises';

const root = resolve('storage/ai-evals');
const port = Number(process.env.AI_EVAL_JUNIT_VIEWER_PORT || 4174);

const contentTypes = new Map([
  ['.html', 'text/html; charset=utf-8'],
  ['.xml', 'application/xml; charset=utf-8'],
  ['.json', 'application/json; charset=utf-8'],
  ['.css', 'text/css; charset=utf-8'],
  ['.js', 'text/javascript; charset=utf-8'],
]);

const server = createServer(async (request, response) => {
  const url = new URL(request.url ?? '/', `http://localhost:${port}`);
  const pathname = url.pathname === '/' ? '/xunit-viewer.html' : url.pathname;
  const path = normalize(join(root, decodeURIComponent(pathname)));

  if (path !== root && ! path.startsWith(root + sep)) {
    response.writeHead(403);
    response.end('Forbidden');

    return;
  }

  try {
    const body = await readFile(path);
    response.writeHead(200, {
      'Content-Type': contentTypes.get(extname(path)) ?? 'application/octet-stream',
    });
    response.end(body);
  } catch {
    response.writeHead(404);
    response.end('Not found');
  }
});

server.listen(port, '127.0.0.1', () => {
  console.log(`Open this URL: http://localhost:${port}/xunit-viewer.html`);
  console.log('Press Ctrl+C to stop the viewer.');
});
