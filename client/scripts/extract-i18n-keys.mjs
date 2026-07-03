import fs from "fs";

const menuSrc = fs.readFileSync("src/config/admin-menu.ts", "utf8");
const routerSrc = fs.readFileSync("src/router/index.ts", "utf8");

const menuEntries = [];
for (const match of menuSrc.matchAll(/\{\s*id:\s*"([^"]+)"[^}]*?label:\s*"([^"]*)"/gs)) {
  menuEntries.push({ id: match[1], label: match[2] });
}

const routeEntries = [];
for (const match of routerSrc.matchAll(/name:\s*"([^"]+)"[^}]*title:\s*"([^"]+)"/g)) {
  routeEntries.push({ name: match[1], title: match[2] });
}

console.log(JSON.stringify({ menuEntries, routeEntries }, null, 2));
