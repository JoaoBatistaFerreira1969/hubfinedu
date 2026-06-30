const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '..', '..', 'data');
const USERS_FILE = path.join(DATA_DIR, 'users.json');

function ensureDataDir() {
  if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
  }
  if (!fs.existsSync(USERS_FILE)) {
    fs.writeFileSync(USERS_FILE, JSON.stringify({ users: [] }, null, 2));
  }
}

function readUsers() {
  ensureDataDir();
  const raw = fs.readFileSync(USERS_FILE, 'utf-8');
  return JSON.parse(raw).users;
}

function writeUsers(users) {
  ensureDataDir();
  fs.writeFileSync(USERS_FILE, JSON.stringify({ users }, null, 2));
}

function findByEmail(email) {
  const users = readUsers();
  return users.find(u => u.email === email.toLowerCase()) || null;
}

function findById(id) {
  const users = readUsers();
  return users.find(u => u.id === id) || null;
}

function findByToken(token) {
  const users = readUsers();
  return users.find(u => u.confirmationToken === token) || null;
}

function createUser(userData) {
  const users = readUsers();
  users.push(userData);
  writeUsers(users);
  return userData;
}

function updateUser(id, updates) {
  const users = readUsers();
  const idx = users.findIndex(u => u.id === id);
  if (idx === -1) return null;
  users[idx] = { ...users[idx], ...updates };
  writeUsers(users);
  return users[idx];
}

module.exports = { findByEmail, findById, findByToken, createUser, updateUser };
