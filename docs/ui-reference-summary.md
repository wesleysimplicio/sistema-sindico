# UI Reference Summary from `docs/print/`

Date: 2026-05-02
Source: 64 screenshots provided by the user

## Confirmed product direction
The screenshots indicate a condominium/syndic app with strong emphasis on:
- resident management
- visitor and service provider access
- vehicles
- QR-code based invitations and portaria notification
- access history and facial-recognition related events
- notices / mural de avisos
- documents
- deliveries
- requests / manifestations / occurrences
- profile, settings, notification preferences, app permissions, and security

## Recurrent visual patterns
- purple top bar
- bottom navigation for main sections
- dashboard/home with shortcut cards
- lists with avatars or icons
- floating action button (+)
- detail screens and forms
- empty states with illustration
- consistent PT-BR labels in the UI

## Recommended initial backend modules
1. Dashboard / shortcuts
2. Authentication scaffold
3. Condominiums
4. Units
5. Residents
6. Visitors
7. Service providers / staff
8. Vehicles
9. Notices
10. Documents
11. Deliveries
12. Requests / manifestations
13. Occurrences
14. Access history
15. QR invitations / portaria alerts
16. Profile / settings / notification preferences

## API implications for future mobile app
Prepare REST-style JSON endpoints with consistent envelope for:
- auth/login, auth/logout, me
- condominiums, units, residents
- visitors, providers, vehicles
- notices, documents, deliveries
- requests, occurrences
- access-history
- invitations/qr-codes
- profile/settings/notification-preferences

## What is still intentionally pending
- pixel-perfect UI implementation based on each screenshot
- real authentication rules
- persistence and repository layer wiring
- permission/security flows beyond scaffold
- media upload handling and QR generation implementation

## Initial naming guidance
Use PT-BR labels in templates where user-facing.
Use English for code, class names, commit messages, and technical docs.
