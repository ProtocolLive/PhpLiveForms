create table forms_forms(
  form_id int unsigned not null auto_increment primary key,
  site varchar(15),
  form varchar(15) not null,
  method varchar(15) not null,
  autocomplete tinyint unsigned not null default 1
);

create table forms_fields(
  field_id int unsigned not null auto_increment primary key,
  form_id int unsigned not null,
  label varchar(50),
  name varchar(15),
  type varchar(15) not null,
  `default` varchar(20),
  mode tinyint unsigned not null default 0,
  size tinyint unsigned,
  style varchar(1024),
  class varchar(10),
  js_event varchar(10),
  js_code varchar(512),
  `order` tinyint unsigned not null default 100
);

alter table forms_fields
  add constraint forms_fields_ibfk_1 foreign key(form_id) references forms_forms(form_id) on delete CASCADE on update CASCADE;