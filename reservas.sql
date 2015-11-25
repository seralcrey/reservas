drop table if exists usuarios cascade;

create table usuarios (
  id        bigserial   constraint pk_usuarios primary key,
  nick      varchar(15) not null constraint uq_usuarios_nick unique,
  password  char(32)    not null constraint ck_password_lleno
                        check (length(password) = 32),
  dni       char(9)     not null constraint uq_usuarios_dni unique,
  nombre    varchar(30) not null,
  apellidos varchar(50)
);

insert into usuarios (nick, password, dni, nombre, apellidos)
values ('pepe', md5('pepe'), '52525252J', 'Pepe', 'Lotas'),
       ('juan', md5('juan'), '12121212A', 'Juan', 'Illo');


drop table if exists pistas cascade;

create table pistas (
  id     bigserial   constraint pk_pistas primary key,
  nombre varchar(30) not null
);

insert into pistas (nombre)
values ('Fútbol'),
       ('Baloncesto'),
       ('Tenis');

drop table if exists reservas cascade;

create table reservas (
  id          bigserial  constraint pk_reservas primary key,
  pistas_id   bigint     not null constraint fk_reservas_pistas
                         references pistas (id) on delete no action
                         on update cascade,
  usuarios_id bigint     not null constraint fk_reservas_usuarios
                         references usuarios (id) on delete no action
                         on update cascade,
  fecha       date       not null,
  hora        numeric(2) not null constraint ck_hora_valida
                         check (hora between 0 and 23),
  constraint uq_reservas_unicas unique (pistas_id, fecha, hora)
);

