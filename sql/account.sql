drop table if exists account;
create table account (
    id              int         unsigned primary key auto_increment,
    name            varchar(64)          not null,
    password        varchar(64)          not null,
    password_salt   varchar(32)          not null,
    email           varchar(64)          not null,
    acl enum('User', 'Developer', 'Admin', 'Super') not null default 'User' comment 'Access Control Level',

    created_date    datetime             not null,
    updated_date    datetime             not null,

    unique(name),
    index(created_date),
    index(name,password,password_salt)
) engine=innodb auto_increment=1;
