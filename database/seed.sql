-- Seed: dados iniciais para desenvolvimento local
-- Senha de demo para todos os usuarios: "senha123"

SET NAMES utf8mb4;

INSERT INTO condominiums (id, name, cnpj, address, city, state, zipcode, phone) VALUES
  (1, 'Parque Santa Ines', '12.345.678/0001-90', 'Rua das Palmeiras, 100', 'Sao Paulo', 'SP', '04500-000', '(11) 4002-8922');

INSERT INTO units (id, condominium_id, block, number, floor, type, area_m2, parking_slots) VALUES
  (1, 1, 'A', '101', '1', 'apartamento', 65.50, 1),
  (2, 1, 'A', '102', '1', 'apartamento', 70.00, 1),
  (3, 1, 'A', '201', '2', 'apartamento', 65.50, 1),
  (4, 1, 'B', '101', '1', 'apartamento', 80.00, 2);

INSERT INTO users (id, condominium_id, unit_id, name, email, password_hash, role, phone) VALUES
  (1, 1, NULL, 'Admin Master', 'admin@sindico.local', '$2y$12$X3h1GZklZHShcqBpWb2gqOnYHLaso7Ng/SfsNW9VvQFjTx7TCauzW', 'admin', '(11) 99999-0001'),
  (2, 1, NULL, 'Sindico Carlos', 'sindico@sindico.local', '$2y$12$X3h1GZklZHShcqBpWb2gqOnYHLaso7Ng/SfsNW9VvQFjTx7TCauzW', 'sindico', '(11) 99999-0002'),
  (3, 1, 1, 'Manoel Da Silva Ramos', 'manoel@example.com', '$2y$12$X3h1GZklZHShcqBpWb2gqOnYHLaso7Ng/SfsNW9VvQFjTx7TCauzW', 'morador', '(11) 99999-0003'),
  (4, 1, 2, 'Ana Souza', 'ana@example.com', '$2y$12$X3h1GZklZHShcqBpWb2gqOnYHLaso7Ng/SfsNW9VvQFjTx7TCauzW', 'morador', '(11) 99999-0004'),
  (5, 1, NULL, 'Porteiro Joao', 'portaria@sindico.local', '$2y$12$X3h1GZklZHShcqBpWb2gqOnYHLaso7Ng/SfsNW9VvQFjTx7TCauzW', 'porteiro', '(11) 99999-0005');

INSERT INTO common_areas (condominium_id, name, description, capacity, opening_time, closing_time, requires_approval) VALUES
  (1, 'Salao de Festas', 'Capacidade para 60 pessoas, com cozinha equipada.', 60, '09:00:00', '23:00:00', 1),
  (1, 'Churrasqueira', 'Area gourmet do bloco A.', 20, '10:00:00', '22:00:00', 0),
  (1, 'Quadra Poliesportiva', 'Quadra coberta.', 30, '07:00:00', '22:00:00', 0),
  (1, 'Piscina', 'Piscina adulto e infantil.', 40, '08:00:00', '20:00:00', 0);

INSERT INTO notices (condominium_id, author_id, title, body, category, pinned, published_at) VALUES
  (1, 2, 'Manutencao do elevador', 'O elevador do bloco A passara por manutencao na quarta-feira das 08h as 12h.', 'manutencao', 1, NOW()),
  (1, 2, 'Reuniao de condominio', 'Convocacao para assembleia geral ordinaria no dia 15.', 'assembleia', 0, NOW()),
  (1, 2, 'Limpeza da caixa d agua', 'Programada para o proximo sabado. Reservar agua.', 'aviso', 0, NOW());

INSERT INTO maintenance_requests (condominium_id, unit_id, requester_id, title, description, category, priority, status) VALUES
  (1, 1, 3, 'Vazamento na pia', 'Pia da cozinha vazando ha 2 dias.', 'hidraulica', 'alta', 'aberto'),
  (1, 2, 4, 'Lampada queimada', 'Corredor do 1 andar bloco A.', 'eletrica', 'media', 'em_andamento');

INSERT INTO payments (condominium_id, unit_id, resident_id, reference, description, amount, due_date, status) VALUES
  (1, 1, 3, '2026-04', 'Taxa condominial 04/2026', 580.00, '2026-04-10', 'pago'),
  (1, 1, 3, '2026-05', 'Taxa condominial 05/2026', 580.00, '2026-05-10', 'pendente'),
  (1, 2, 4, '2026-05', 'Taxa condominial 05/2026', 620.00, '2026-05-10', 'pendente');

INSERT INTO deliveries (condominium_id, unit_id, resident_id, sender, courier, tracking_code, description, status) VALUES
  (1, 1, 3, 'Mercado Livre', 'Correios', 'BR123456789', 'Caixa media', 'aguardando'),
  (1, 2, 4, 'Amazon', 'Loggi', 'AM987654321', 'Envelope', 'aguardando');

INSERT INTO visitors (condominium_id, unit_id, host_id, name, document, qr_token, expected_at, status) VALUES
  (1, 1, 3, 'Pedro Visitante', '111.222.333-44', SHA2(CONCAT('seed-1-', NOW()), 256), DATE_ADD(NOW(), INTERVAL 1 DAY), 'previsto');

INSERT INTO documents (condominium_id, uploaded_by, title, description, file_path, category, mime_type) VALUES
  (1, 2, 'Convencao Condominial', 'Documento oficial de convencao.', '/storage/docs/convencao.pdf', 'oficial', 'application/pdf'),
  (1, 2, 'Regulamento Interno', 'Normas de convivencia.', '/storage/docs/regulamento.pdf', 'oficial', 'application/pdf');

INSERT INTO messages (condominium_id, from_user_id, to_user_id, subject, body, channel) VALUES
  (1, 3, 2, 'Duvida sobre taxa', 'Boa tarde, gostaria de tirar uma duvida sobre o boleto.', 'sindico');

INSERT INTO memberships (user_id, condominium_id, role, is_active) VALUES
  (1, 1, 'admin',    1),
  (2, 1, 'sindico',  1),
  (3, 1, 'morador',  1),
  (4, 1, 'morador',  1),
  (5, 1, 'porteiro', 1);
