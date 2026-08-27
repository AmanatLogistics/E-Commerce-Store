// Blocks every /admin page except the login screen until a session cookie exists.
import { isLoggedIn } from './lib/auth.js';

export function onRequest({ url, cookies, redirect }, next) {
  const needsAuth =
    url.pathname.startsWith('/admin') && url.pathname !== '/admin/login';

  if (needsAuth && !isLoggedIn(cookies)) {
    return redirect('/admin/login');
  }
  return next();
}
