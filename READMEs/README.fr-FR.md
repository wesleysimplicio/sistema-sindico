<h1 align="center">Sistema Sindico</h1>

<p align="center">
  <strong>Système de gestion de copropriété en PHP 8.2 + MySQL avec panneau admin server-rendered et API REST prête pour mobile.</strong><br />
  <em>Les commandes restent en anglais pour être copiées exactement.</em>
</p>

<p align="center">
<a href="https://github.com/wesleysimplicio/sistema-sindico/stargazers"><img alt="GitHub stars" src="https://img.shields.io/github/stars/wesleysimplicio/sistema-sindico?style=flat-square" /></a>
<img alt="PHP 8.2" src="https://img.shields.io/badge/PHP-8.2-777bb4?style=flat-square" />
<img alt="MySQL 8" src="https://img.shields.io/badge/MySQL-8-4479a1?style=flat-square" />
</p>

<p align="center">
<a href="../README.md">English</a> | <a href="README.pt-BR.md">Português</a> | <a href="README.es-ES.md">Español</a> | <a href="README.ja-JP.md">日本語</a> | <a href="README.ko-KR.md">한국어</a> | <a href="README.zh-CN.md">简体中文</a> | <a href="README.it-IT.md">Italiano</a> | <a href="README.fr-FR.md">Français</a> | <a href="README.ru-RU.md">Русский</a> | <a href="README.pl-PL.md">Polski</a> | <a href="README.hi-IN.md">हिन्दी</a> | <a href="README.ar-SA.md">العربية</a> | <a href="README.he-IL.md">עברית</a> | <a href="README.ms-MY.md">Bahasa Melayu</a> | <a href="README.id-ID.md">Bahasa Indonesia</a>
</p>



---

## Résumé court

Système de gestion de copropriété en PHP 8.2 + MySQL avec panneau admin server-rendered et API REST prête pour mobile.

## ADN du projet

Cette page localisee garde le chemin rapide. Le guide technique restaure se trouve dans le README racine afin de conserver la voix originale et les details operationnels du projet.

- Full restored guide: [../README.md](../README.md)
- Local project note: sistema-sindico is the real product anchor in this workspace: condominium management in PHP/MySQL with roles, payments, reservations, documents, and operational workflows. The README should feel like software someone can run and maintain, not only a branded shell, so the original setup and domain guide is restored.

## Démarrage rapide

```bash
cp .env.example .env
docker compose up -d --build
curl -s http://127.0.0.1:8000/api/health
```

## Ce que ça fait

- Session-based admin area for sindico/admin roles.
- JWT API prepared for residents, gate staff and future mobile clients.
- Tenant safety through condominium_id scoped domain tables.
- Docker onboarding with MySQL seed and local mail log defaults.

## Pourquoi ce README est conçu pour attirer l’attention

- promesse claire dès le premier écran
- liens de langues avant l’installation
- badges et hero pour la confiance
- quick start copiable
- preuves avant les longs détails
- historique des étoiles comme preuve sociale

## Fonctionnement

```mermaid
flowchart LR
  mapper["simplicio-mapper
repo context"] --> current["Sistema Sindico
this project"]
  prompt["simplicio-prompt
reasoning runtime"] --> current
  current --> evidence["validated evidence
tests, docs, screenshots"]
  current --> sprint["simplicio-sprint
delivery loop"]
```

## Preuves et validation

- PHPUnit, Postman/Newman and Playwright flows exist for regression.
- Changelog records security, rate limit, Docker and E2E hardening.
- Mapper failed on this repo in the current run because .starter-meta.json says dotnet while the real stack is PHP; README now documents the true stack.

## Écosystème Simplicio

- [simplicio-mapper](https://github.com/wesleysimplicio/simplicio-mapper) supplies repo context before interpretation.
- [simplicio-cli](https://github.com/wesleysimplicio/simplicio-dev-cli) executes focused code tasks with verification.
- [simplicio-prompt](https://github.com/wesleysimplicio/simplicio-prompt) provides fan-out and consensus runtime patterns.
- [simplicio-sprint](https://github.com/wesleysimplicio/simplicio-sprint) turns cards into draft PR delivery loops.

## Standard de documentation

- [AGENTS.md](../AGENTS.md)
- [CHANGELOG.md](../CHANGELOG.md)
- [docs/readme-globalization-standard.md](../docs/readme-globalization-standard.md)

## Historique des étoiles

<a href="https://www.star-history.com/#wesleysimplicio/sistema-sindico&Date">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date&theme=dark" />
    <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date" />
    <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date" />
  </picture>
</a>

## Licence

See the repository license and distribution notes before production use.
