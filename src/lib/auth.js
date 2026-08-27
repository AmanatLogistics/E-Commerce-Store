// Very small session helper: one shared admin password, one signed cookie.
// Good enough for a private internal panel; swap for real user accounts if the
// panel ever needs more than one login.
import { createHash, timingSafeEqual } from 'node:crypto';
import { ADMIN_PASSWORD } from './config.js';

const COOKIE = 'admin_session';

const token = () =>
  createHash('sha256').update(`amanat:${ADMIN_PASSWORD}`).digest('hex');

function matches(a, b) {
  const left = Buffer.from(String(a));
  const right = Buffer.from(String(b));
  return left.length === right.length && timingSafeEqual(left, right);
}

export function checkPassword(input) {
  return matches(input ?? '', ADMIN_PASSWORD);
}

export function isLoggedIn(cookies) {
  return matches(cookies.get(COOKIE)?.value ?? '', token());
}

export function login(cookies) {
  cookies.set(COOKIE, token(), {
    path: '/',
    httpOnly: true,
    sameSite: 'lax',
    maxAge: 60 * 60 * 8,
  });
}

export function logout(cookies) {
  cookies.delete(COOKIE, { path: '/' });
}
