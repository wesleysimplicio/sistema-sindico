<h1 align="center">Sistema Sindico</h1>

<p align="center">
  <strong>PHP 8.2 + MySQL のマンション管理システムです。サーバーレンダリング管理画面とモバイル向け REST API を備えています。</strong><br />
  <em>コマンドはそのままコピーできるよう英語で記載しています。</em>
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

## 短い概要

PHP 8.2 + MySQL のマンション管理システムです。サーバーレンダリング管理画面とモバイル向け REST API を備えています。

## プロジェクトのDNA

このローカライズ版は最短導線を保ちます。復元された詳細ガイドはルート README にあり、プロジェクト本来の声と運用情報を残します。

- Full restored guide: [../README.md](../README.md)
- Local project note: sistema-sindico is the real product anchor in this workspace: condominium management in PHP/MySQL with roles, payments, reservations, documents, and operational workflows. The README should feel like software someone can run and maintain, not only a branded shell, so the original setup and domain guide is restored.

## クイックスタート

```bash
cp .env.example .env
docker compose up -d --build
curl -s http://127.0.0.1:8000/api/health
```

## できること

- Session-based admin area for sindico/admin roles.
- JWT API prepared for residents, gate staff and future mobile clients.
- Tenant safety through condominium_id scoped domain tables.
- Docker onboarding with MySQL seed and local mail log defaults.

## 注目される README にした理由

- 最初の画面で価値を明確に伝える
- インストール前に言語リンクを置く
- バッジと hero 画像で信頼を作る
- コピーできる quick start
- 長い説明より先に検証を置く
- スター履歴で社会的証明を見せる

## 仕組み

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

## 証拠と検証

- PHPUnit, Postman/Newman and Playwright flows exist for regression.
- Changelog records security, rate limit, Docker and E2E hardening.
- Mapper failed on this repo in the current run because .starter-meta.json says dotnet while the real stack is PHP; README now documents the true stack.

## Simplicio エコシステム

- [simplicio-mapper](https://github.com/wesleysimplicio/simplicio-mapper) supplies repo context before interpretation.
- [simplicio-cli](https://github.com/wesleysimplicio/simplicio-dev-cli) executes focused code tasks with verification.
- [simplicio-prompt](https://github.com/wesleysimplicio/simplicio-prompt) provides fan-out and consensus runtime patterns.
- [simplicio-sprint](https://github.com/wesleysimplicio/simplicio-sprint) turns cards into draft PR delivery loops.

## ドキュメント標準

- [AGENTS.md](../AGENTS.md)
- [CHANGELOG.md](../CHANGELOG.md)
- [docs/readme-globalization-standard.md](../docs/readme-globalization-standard.md)

## スター履歴

<a href="https://www.star-history.com/#wesleysimplicio/sistema-sindico&Date">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date&theme=dark" />
    <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date" />
    <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=wesleysimplicio/sistema-sindico&type=Date" />
  </picture>
</a>

## ライセンス

See the repository license and distribution notes before production use.
