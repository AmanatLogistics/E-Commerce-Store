import { logout } from '../../lib/auth.js';

export function POST({ cookies, redirect }) {
  logout(cookies);
  return redirect('/');
}
