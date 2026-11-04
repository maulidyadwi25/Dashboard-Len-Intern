BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "cache" (
	"key"	varchar NOT NULL,
	"value"	text NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks" (
	"key"	varchar NOT NULL,
	"owner"	varchar NOT NULL,
	"expiration"	integer NOT NULL,
	PRIMARY KEY("key")
);
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id"	integer NOT NULL,
	"uuid"	varchar NOT NULL,
	"connection"	text NOT NULL,
	"queue"	text NOT NULL,
	"payload"	text NOT NULL,
	"exception"	text NOT NULL,
	"failed_at"	datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "gantt_activities" (
	"id"	INTEGER,
	"master_project_id"	INTEGER NOT NULL,
	"activity_name"	VARCHAR NOT NULL,
	"start_date"	DATE,
	"end_date"	DATE,
	"progress_percentage"	INTEGER DEFAULT 0,
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("master_project_id") REFERENCES "master_projects"("id")
);
CREATE TABLE IF NOT EXISTS "job_batches" (
	"id"	varchar NOT NULL,
	"name"	varchar NOT NULL,
	"total_jobs"	integer NOT NULL,
	"pending_jobs"	integer NOT NULL,
	"failed_jobs"	integer NOT NULL,
	"failed_job_ids"	text NOT NULL,
	"options"	text,
	"cancelled_at"	integer,
	"created_at"	integer NOT NULL,
	"finished_at"	integer,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "jobs" (
	"id"	integer NOT NULL,
	"queue"	varchar NOT NULL,
	"payload"	text NOT NULL,
	"attempts"	integer NOT NULL,
	"reserved_at"	integer,
	"available_at"	integer NOT NULL,
	"created_at"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "master_projects" (
	"id"	INTEGER,
	"project_code"	VARCHAR NOT NULL,
	"project_name"	VARCHAR NOT NULL,
	"spreadsheet_id"	VARCHAR,
	"description"	TEXT,
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	"type"	varchar,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "migrations" (
	"id"	integer NOT NULL,
	"migration"	varchar NOT NULL,
	"batch"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email"	varchar NOT NULL,
	"token"	varchar NOT NULL,
	"created_at"	datetime,
	PRIMARY KEY("email")
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
	"id"	integer NOT NULL,
	"tokenable_type"	varchar NOT NULL,
	"tokenable_id"	integer NOT NULL,
	"name"	text NOT NULL,
	"token"	varchar NOT NULL,
	"abilities"	text,
	"last_used_at"	datetime,
	"expires_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "procurements" (
	"id"	INTEGER,
	"master_project_id"	INTEGER NOT NULL,
	"no"	VARCHAR NOT NULL,
	"name"	VARCHAR NOT NULL,
	"vendor"	VARCHAR,
	"nilai_rab"	DECIMAL(15, 2),
	"realisasi"	DECIMAL(15, 2),
	"mata_uang"	VARCHAR DEFAULT 'IDR',
	"status"	VARCHAR DEFAULT 'In Progress',
	"progress"	VARCHAR,
	"priority"	VARCHAR DEFAULT 'Medium',
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	"target_tgl"	varchar,
	"jenis"	varchar,
	"payment_type"	varchar,
	"proc_policy"	varchar,
	"no_hps"	varchar,
	"tanggal_hps"	varchar,
	"rks"	varchar,
	"tanggal_rks"	varchar,
	"pic_ome"	varchar,
	"no_pr"	varchar,
	"proc_ops"	varchar,
	"no_po"	varchar,
	"kontrak_status"	varchar,
	"due_date"	varchar,
	"payment_priority"	varchar,
	"delivery_status"	varchar,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("master_project_id") REFERENCES "master_projects"("id")
);
CREATE TABLE IF NOT EXISTS "project_links" (
	"id"	INTEGER,
	"master_project_id"	INTEGER NOT NULL,
	"sheet_name"	VARCHAR NOT NULL,
	"sheet_url"	VARCHAR,
	"sheet_key"	VARCHAR,
	"data"	TEXT,
	"row_count"	INTEGER,
	"last_synced_at"	DATETIME,
	"created_at"	DATETIME,
	"updated_at"	DATETIME,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("master_project_id") REFERENCES "master_projects"("id")
);
CREATE TABLE IF NOT EXISTS "project_profiles" (
	"id"	integer NOT NULL,
	"master_project_id"	integer NOT NULL,
	"type"	varchar,
	"project_name"	varchar,
	"pimpro_name"	varchar,
	"report_pic"	varchar,
	"contract_value"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("master_project_id") REFERENCES "master_projects"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "sessions" (
	"id"	varchar NOT NULL,
	"user_id"	integer,
	"ip_address"	varchar,
	"user_agent"	text,
	"payload"	text NOT NULL,
	"last_activity"	integer NOT NULL,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "system_syncs" (
	"id"	integer NOT NULL,
	"key"	varchar NOT NULL,
	"last_synced_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "tasks" (
	"id"	integer NOT NULL,
	"task_id"	varchar NOT NULL,
	"task_name"	varchar NOT NULL,
	"start_date"	date,
	"end_date"	date,
	"plan_date"	date,
	"actual_date"	date,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "users" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"email"	varchar NOT NULL,
	"email_verified_at"	datetime,
	"password"	varchar NOT NULL,
	"remember_token"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
INSERT INTO "cache" VALUES ('laravel-cache-cdn_currency_exchange_rates_v2','a:7:{s:7:"success";b:1;s:6:"source";s:44:"cdn.jsdelivr.net (@fawazahmed0/currency-api)";s:4:"date";s:10:"2026-10-13";s:6:"to_idr";a:4:{s:3:"IDR";i:1;s:3:"USD";d:17926;s:3:"EUR";d:20032.51;s:3:"SGD";d:13997.69;}s:8:"from_idr";a:4:{s:3:"IDR";i:1;s:3:"USD";d:5.578E-5;s:3:"EUR";d:4.992E-5;s:3:"SGD";d:7.144E-5;}s:7:"symbols";a:4:{s:3:"IDR";s:2:"Rp";s:3:"USD";s:1:"$";s:3:"EUR";s:3:"€";s:3:"SGD";s:2:"S$";}s:6:"labels";a:4:{s:3:"IDR";s:17:"Indonesian Rupiah";s:3:"USD";s:9:"US Dollar";s:3:"EUR";s:4:"Euro";s:3:"SGD";s:16:"Singapore Dollar";}}',1791883416);
INSERT INTO "gantt_activities" VALUES (50296,1,'Inisiasi & Inpeksi','2026-08-22','2026-08-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50297,1,'Inisiasi & Inpeksi',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50298,1,'Kick-off & Charter','2026-01-08','2026-01-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50299,1,'Kick-off & Charter','2026-01-08','2026-01-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50300,1,'Pekerjaan Permukaan & Lambung',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50301,1,'Pekerjaan Permukaan & Lambung',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50302,1,'Replating Lambung	2',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50303,1,'Replating Lambung	2',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50304,1,'Penggantian Pelat Area Pelat Dasar','2026-02-08','2026-02-28',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50305,1,'Penggantian Pelat Area Pelat Dasar','2026-02-15','2026-03-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50306,1,'Sandblasting & Cat Lambung','2026-02-22','2026-03-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50307,1,'Sandblasting & Cat Lambung','2026-03-08','2026-03-21',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50308,1,'Pekerjaan Permukaan & Lambung',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50309,1,'Pekerjaan Permukaan & Lambung',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50310,1,'Overhaul Mesin Utama (ME)',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50311,1,'Overhaul Mesin Utama (ME)',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50312,1,'Demontasi & Replacement Piston/Liner','2026-03-08','2026-03-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50313,1,'Demontasi & Replacement Piston/Liner','2026-03-08','2026-04-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50314,1,'Alignment Shaft & Balans Propeler','2026-04-01','2026-04-30',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50315,1,'Alignment Shaft & Balans Propeler','2026-04-08','2026-04-30',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50316,1,'Modernisasi Sewaco & Kelistrikan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50317,1,'Modernisasi Sewaco & Kelistrikan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50318,1,'Instalasi Kabel & Navigasi',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50319,1,'Instalasi Kabel & Navigasi',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50320,1,'Wiring & Panel Switchboard Utama','2026-05-08','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50321,1,'Wiring & Panel Switchboard Utama','2026-05-08','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50322,1,'End User Certificate',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50323,1,'Engineering Document','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50324,1,'Engineering Document',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50325,1,'OPQ & RST',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50326,1,'OPQ & RST',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50327,1,'OPQ Pengadaan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50328,1,'OPQ Pengadaan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50329,1,'Purchase Order',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50330,1,'Purchase Order',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50331,1,'Kontrak Integrasi OPQ','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50332,1,'Kontrak Integrasi OPQ',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50333,1,'Down Payment Integrasi OPQ','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50334,1,'Down Payment Integrasi OPQ',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50335,1,'End User Certificate','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50336,1,'End User Certificate',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50337,1,'Engineering Document','2026-05-22','2026-11-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50338,1,'Engineering Document',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50339,1,'Scope Of Work Perbaikan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50340,1,'Scope Of Work Perbaikan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50341,1,'Bill of Material (BOM)','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50342,1,'Bill of Material (BOM)','2026-07-01','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50343,1,'ZWX & STU','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50344,1,'ZWX & STU','2026-08-08','2026-08-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50345,1,'ZWX Pengadaan','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50346,1,'ZWX Pengadaan',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50347,1,'Purchase Order','2026-05-22','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50348,1,'Purchase Order',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50349,1,'Kontrak','2026-06-08','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50350,1,'Kontrak',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50351,1,'Down Payment','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50352,1,'Down Payment',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50353,1,'Engineering Document','2026-08-15','2026-10-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50354,1,'Engineering Document',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50355,1,'Product Spec All Scope','2026-03-08','2026-04-30',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50356,1,'Product Spec All Scope','2026-03-08','2026-04-30',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50357,1,'Approval Desain Gambar','2026-11-08','2026-11-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50358,1,'Approval Desain Gambar',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50359,1,'CDR Activity','2026-11-08','2026-11-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50360,1,'CDR Activity',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50361,1,'End User Certificate','2026-05-01','2026-07-21',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50362,1,'End User Certificate',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50363,1,'Bill of Material (BOM)','2026-06-01','2026-06-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50364,1,'Bill of Material (BOM)','2026-07-01','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50365,1,'ABCDE & FGHJ','2026-07-01','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50366,1,'ABCDE & FGHJ','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50367,1,'CDE Pengadaan','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50368,1,'CDE Pengadaan','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50369,1,'Purchase Order','2026-07-15','2026-08-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50370,1,'Purchase Order',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50371,1,'Kontrak FGH & CDE','2026-08-08','2026-08-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50372,1,'Kontrak FGH & CDE',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50373,1,'Down Payment FGH & CDE','2026-09-01','2026-10-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50374,1,'Down Payment FGH & CDE',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50375,1,'Work Progress Report','2026-01-01','2026-12-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50376,1,'Work Progress Report','2026-01-01','2026-05-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50377,1,'Bill of Material (BOM)','2026-06-08','2026-06-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50378,1,'Bill of Material (BOM)','2026-06-22','2026-06-30',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50379,1,'SPH & HPS','2026-07-01','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50380,1,'SPH & HPS','2026-07-01','2026-07-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50381,1,'RKS Pengadaan','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50382,1,'RKS Pengadaan','2026-07-08','2026-07-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50383,1,'Purchase Order','2026-07-15','2026-08-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50384,1,'Purchase Order','2026-07-22','2026-08-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50385,1,'Kontrak Cable','2026-08-08','2026-09-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50386,1,'Kontrak Cable',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50387,1,'Down Payment Cable','2026-09-08','2026-10-07',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50388,1,'Down Payment Cable',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50389,1,'Interface Control Document','2026-06-15','2026-10-14',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50390,1,'Interface Control Document',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50391,1,'Work Progress Report','2026-04-01','2026-12-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50392,1,'Work Progress Report','2026-04-01','2026-05-31',0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50393,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50394,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50395,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50396,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50397,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50398,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50399,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50400,1,'Work Progress Report',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "gantt_activities" VALUES (50401,1,'Interface Control Document',NULL,NULL,0,'2026-10-13 08:21:26','2026-10-13 08:21:26');
INSERT INTO "master_projects" VALUES (1,'B-1234567','Refurbishment & Modernisasi Sistem Senjata KRI','1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70',NULL,'2026-09-21 02:47:43','2026-10-13 03:24:27',NULL);
INSERT INTO "migrations" VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO "migrations" VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO "migrations" VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO "migrations" VALUES (4,'2026_08_20_054238_create_personal_access_tokens_table',1);
INSERT INTO "migrations" VALUES (5,'2026_08_20_065335_create_tasks_table',1);
INSERT INTO "migrations" VALUES (6,'2026_08_20_065415_create_system_syncs_table',1);
INSERT INTO "migrations" VALUES (7,'2026_09_09_000001_create_sheets_and_monitoring_tables',1);
INSERT INTO "migrations" VALUES (8,'2026_09_13_000001_add_master_project_relations',1);
INSERT INTO "migrations" VALUES (9,'2026_09_14_075903_rename_projects_to_masterprojects_and_sheet_data_to_project_links',1);
INSERT INTO "migrations" VALUES (10,'2026_09_15_112038_add_master_project_id_to_child_tables',1);
INSERT INTO "migrations" VALUES (11,'2026_09_15_112236_reorder_master_project_id_after_id',1);
INSERT INTO "migrations" VALUES (12,'2026_09_15_112420_drop_project_id_keep_master_project_id',1);
INSERT INTO "migrations" VALUES (13,'2026_09_15_112909_rebuild_to_erd',1);
INSERT INTO "migrations" VALUES (14,'2026_09_15_113326_restore_detail_keep_erd',1);
INSERT INTO "migrations" VALUES (15,'2026_09_16_015633_rebuild_all_tables_to_erd',1);
INSERT INTO "migrations" VALUES (16,'2026_10_01_064008_add_sheet_columns_to_procurements_table',2);
INSERT INTO "migrations" VALUES (17,'2026_10_06_085648_create_project_profiles_table',3);
INSERT INTO "migrations" VALUES (18,'2026_10_07_025325_add_type_to_master_projects_table',4);
INSERT INTO "migrations" VALUES (19,'2026_10_07_065041_remove_client_name_from_project_profiles_table',5);
INSERT INTO "migrations" VALUES (20,'2026_10_07_071035_remove_project_code_and_progress_from_project_profiles_table',6);
INSERT INTO "migrations" VALUES (21,'2026_10_07_084130_add_type_to_project_profiles_table',7);
INSERT INTO "migrations" VALUES (22,'2026_10_08_021624_reorder_type_column_in_project_profiles_table',8);
INSERT INTO "migrations" VALUES (23,'2026_10_13_071652_add_payment_delivery_columns_to_procurements_table',9);
INSERT INTO "procurements" VALUES (13107,1,'1','Overall PMO & System + Ground Segment + Training','ALENIA',92300123,22300891,'EUR','In Progress','SPH','High','2026-10-13 08:21:27','2026-10-13 08:21:27','27/11/2026','JASA','Transfer','DEA','123/HPS/UJ-1/DHN/XI/2023','12 Nov 2023','142/RKS/UJ/XI/2023','22 Nov 2023','LINDA','123','adnan','44','DONE','22-May-2025','HIGH','-');
INSERT INTO "procurements" VALUES (13108,1,'2','Optical Satellite','ALENIA',88724647,78727247,'EUR','In Progress','KONTRAK','High','2026-10-13 08:21:27','2026-10-13 08:21:27','28/11/2026','BARANG','Transfer','DEA','456/HPS/UJ-1/DHN/XI/2023','12 Nov 2023','142/RKS/UJ/XI/2023','22 Nov 2023','LINDA','123','adnan','44','DONE','23-May-2025','HIGH','NOT YET');
INSERT INTO "procurements" VALUES (13109,1,'3','Imagery Custom Subscription','BLACKSKY LLC',10010000,10010000,'USD','In Progress','KONTRAK','High','2026-10-13 08:21:27','2026-10-13 08:21:27','03/12/2026','BARANG','Transfer','DEA','786/HPS/UJ-1/ILS/XII/2023','01 Dec 2023','169/RKS/UJ/XII/2023','07 Dec 2023','LINDA','123','adnan','44','DONE','27-Jan-2025','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13110,1,'4','Pengadaan Barang A','PT Nusantara Tech Solusindo',0,3225.81,'','Done','DELIVERED','High','2026-10-13 08:21:27','2026-10-13 08:21:27','10/04/2026','BARANG & JASA','Transfer','DIANASTI','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13111,1,'5','Pengadaan Server & Infrastructure Modernization','CV Cipta Karya Informatika',0,18000000,'','In Progress','NEGOSIASI','High','2026-10-13 08:21:27','2026-10-13 08:21:27','10/09/2026','BARANG & JASA','Transfer','DIANASTI','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13112,1,'5.1','Ruggedized Marine Rackmount Server Node (Shock & Vibration Proof)','CV Cipta Karya Informatika',0,7741.94,'','Done','DELIVERED','High','2026-10-13 08:21:27','2026-10-13 08:21:27','08/09/2026','JASA','Transfer','DIANASTI','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13113,1,'5.2','Naval Network Switch & Fiber Optic Transceiver Set (Vessel Modernization)','CV Cipta Karya Informatika',0,3008.47,'','Done','DELIVERED','High','2026-10-13 08:21:27','2026-10-13 08:21:27','09/09/2026','BARANG','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13114,1,'5.3','Marine-Grade Online UPS & Redundant Power Unit (Dual Input AC/DC)','CV Cipta Karya Informatika',0,4411.76,'','In Progress','KONTRAK','High','2026-10-13 08:21:27','2026-10-13 08:21:27','10/09/2026','BARANG','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13115,1,'6','Jasa Maintenance Software Management System','PT Graha Informatika Utama',0,27500000,'','In Progress','RKS','High','2026-10-13 08:21:27','2026-10-13 08:21:27','12/09/2026','BARANG & JASA','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13116,1,'7','Pengadaan Laptop Workstation Tim UI/UX & Dev','PT Digital Studio Indonesia',0,593.55,'','In Progress','RKS','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','14/09/2026','BARANG','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13117,1,'7.1','MacBook Pro 16" M3 Max (36GB RAM, 1TB SSD) — UI/UX Lead & Sr. Dev','PT Digital Studio Indonesia',0,7941.18,'','Done','DELIVERED','Low','2026-10-13 08:21:27','2026-10-13 08:21:27','10/09/2026','BARANG','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13118,1,'7.2','Windows Workstation Laptop (32GB RAM, 1TB SSD, RTX Graphics) — Dev Team','PT Digital Studio Indonesia',0,5588.24,'','Done','DELIVERED','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','12/09/2026','BARANG','Transfer','DIANASTI','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13119,1,'8','Jasa Konsultasi Redesign UI/UX Dashboard PMO','PT Jaringan Cyber Nusantara',0,31000000,'','In Progress','HPS','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','15/09/2026','BARANG & JASA','Transfer','DIANASTI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13120,1,'9','Pengadaan Perangkat Network Switch & Router','PT Awan Data Mitra',0,3419.35,'','In Progress','RKS','High','2026-10-13 08:21:27','2026-10-13 08:21:27','15/09/2026','JASA','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13121,1,'10','Jasa Sewa Cloud Server & Domain Tahun 2026','CV Media Global Perdana',0,9354.84,'','In Progress','PR','High','2026-10-13 08:21:27','2026-10-13 08:21:27','16/09/2026','BARANG','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13122,1,'11','Pengadaan Lisensi Software Design & Visualizer','PT Academy Talenta Indonesia',0,27500000,'','In Progress','RKS','High','2026-10-13 08:21:27','2026-10-13 08:21:27','17/09/2026','BARANG','Transfer','MARZUKI','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13123,1,'12','Jasa Pelatihan & Sertifikasi Scrum Master','CV Mandiri Jaya Paper',0,7457.63,'','In Progress','RKS','High','2026-10-13 08:21:27','2026-10-13 08:21:27','17/09/2026','BARANG & JASA','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13124,1,'13','Pengadaan ATK & Perlengkapan Operasional Kantor','PT Secure Shield Consult',0,0,'','Done','DELIVERED','High','2026-10-13 08:21:27','2026-10-13 08:21:27','18/09/2026','JASA','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13125,1,'14','Jasa Audit Keamanan Siber & Penetration Testing','PT Visual Electro Perkasa',0,0,'','In Progress','PR','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','20/09/2026','BARANG','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13126,1,'15','Pengadaan Display Monitor & Video Conference Kit','CV Cool Air Engineering',0,0,'','In Progress','RKS','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','21/09/2026','BARANG','Transfer','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13127,1,'16','Jasa Pemeliharaan Rutin AC & Fasilitas Kantor','PT Mega Data Solution',0,0,'','In Progress','PR','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','22/09/2026','BARANG & JASA','Transfer','DEA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13128,1,'17','Jasa Langganan API Data Analytics & Visualization','PT Analitika Sistem Indonesia',0,0,'','Done','DELIVERED','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','23/09/2026','JASA','Transfer','DEA','','','','','','','','','','','MEDIUM','DELIVERED');
INSERT INTO "procurements" VALUES (13129,1,'18','Pengadaan Furnitur Ergonomis Ruang Kerja','CV Mebel Utamo Design',0,0,'','In Progress','RKS','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','24/09/2026','BARANG & JASA','Tunai','DEA','','','','','','','','','','','HIGH','NOT YET');
INSERT INTO "procurements" VALUES (13130,1,'18.1','Kursi Kerja Ergonomis Mesh (Adjustable Lumbar Support & Headrest)','CV Mebel Utamo Design',0,0,'','Done','DELIVERED','Low','2026-10-13 08:21:27','2026-10-13 08:21:27','20/09/2026','BARANG','Tunai','DEA','','','','','','','','','','','HIGH','NOT YET');
INSERT INTO "procurements" VALUES (13131,1,'18.2','Meja Kerja Motorized Electric Standing Desk (Dual Motor)','CV Mebel Utamo Design',0,0,'','Done','DELIVERED','Low','2026-10-13 08:21:27','2026-10-13 08:21:27','22/09/2026','BARANG & JASA','Tunai','HANA','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13132,1,'18.3','Dual Monitor Arm Heavy Duty (Gas Spring Desk Mount)','CV Mebel Utamo Design',0,0,'','Done','DELIVERED','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','23/09/2026','JASA','Tunai','MARZUKI','','','','','','','','','','','HIGH','NOT YET');
INSERT INTO "procurements" VALUES (13133,1,'18.4','Footrest Ergonomis & Cable Management Tray Set','CV Mebel Utamo Design',0,0,'','In Progress','PO','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','25/09/2026','BARANG','Tunai','MARZUKI','','','','','','','','','','','LOW','NOT YET');
INSERT INTO "procurements" VALUES (13134,1,'19','Jasa Sewa Cloud Server & Domain Tahun 2026','PT Mega Data Solution',0,0,'','Warning','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','22/09/2026','','Transfer','MARZUKI','','','','','','','','','','','MEDIUM','NOT YET');
INSERT INTO "procurements" VALUES (13135,1,'20','Pengadaan Lisensi Software Design & Visualizer','PT Analitika Sistem Indonesia',0,0,'','Warning','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','23/09/2026','','Transfer','MARZUKI','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13136,1,'21','Jasa Pelatihan & Sertifikasi Scrum Master','',0,0,'','Open','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','30/09/2026','','Transfer','MARZUKI','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13137,1,'22','JASA JASAANYA','PT Analitika Sistem Indonesia',0,0,'','Warning','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','22/09/2026','','Transfer','HANA','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13138,1,'23','Jasa Raharja','PT Mencari Kopi',0,0,'','Open','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','30/09/2026','','Transfer','HANA','','','','','','','','','','','HIGH','DELIVERED');
INSERT INTO "procurements" VALUES (13139,1,'24','Pengadaan ngada ngada','PT Ngadi Ngadi Nusantara',0,0,'','Warning','-','Medium','2026-10-13 08:21:27','2026-10-13 08:21:27','27/09/2026','','Transfer','HANA','','','','','','','','','','','LOW','DELIVERED');
INSERT INTO "procurements" VALUES (13140,1,'25','Jasa Pahlawan','PT Nasionalisme',10010000,10010000,'USD','In Progress','KONTRAK','High','2026-10-13 08:21:27','2026-10-13 08:21:27','03/12/2026','BARANG','Transfer','DEA','786/HPS/UJ-1/ILS/XII/2023','01 Dec 2023','169/RKS/UJ/XII/2023','07 Dec 2023','LINDA','123','adnan','44','DONE','27-Jan-2025','LOW','DELIVERED');
INSERT INTO "project_links" VALUES (1,1,'2. Progress & Revenue','https://docs.google.com/spreadsheets/d/1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70','progress_revenue','[["PENTING : "],["A.","Data (P) Planing wajib diinput sampai Desember 2026 "],["B.","16.79%"],["C.","Dilarang mengubah format, menambah kolom dan mengubah tipe data"],["Nama Client","","TNI"],["Kode Proyek","","PRJ-2026-KRI-004"],["Nama Proyek ","","Refurbishment & Modernisasi Sistem Senjata KRI"],["Nilai Kontrak ","","150000000000","447,777,553"],["% LKP Des 2025","","40.56%"],["Progress Tercapai","","5.000%","","","","","","1","1","1","1","2","2","2","2","3","3","3","3","4","4","4","4","5","5","5","5","6","6","6","6","7","7","7","7","8","8","8","8","9","9","9","9","10","10","10","10","11","11","11","11","12","12","12","12"],["Anual Deviasi Target","","-1.123%","","","Week","","Des 2025","Jan W1 ","Jan W2 ","Jan W3 ","Jan W4 ","Feb W1","Feb W2","Feb W3","Feb W4","Mar W1","Mar W2","Mar W3","Mar W4","Apr W1","Apr W2","Apr W3","Apr W4","Mei W1","Mei W2","Mei W3","Mei W4","Jun W1","Jun W2","Jun W3","Jun W4","Jul W1","Jul W2","Jul W3","Jul W4","Aug W1","Aug W2","Aug W3","Aug W4","Sep W1","Sep W2","Sep W3","Sep W4","Okt W1","Okt W2","Okt W3","Okt W4","Nov W1","Nov W2","Nov W3","Nov W4","Dec W1","Dec W2","Dec W3","Dec W4"],["Target Progress 2026","","2.11%","","","Rencana Mingguan RKAP","","5.00%","0.050%","0.050%","0.050%","0.050%","0.063%","0.063%","0.063%","0.063%","0.075%","0.075%","0.075%","0.075%","0.088%","0.088%","0.088%","0.088%","0.100%","0.100%","0.100%","0.100%","0.113%","0.113%","0.113%","0.113%","0.125%","0.125%","0.125%","0.125%","0.138%","0.138%","0.138%","0.138%","0.138%","0.138%","0.138%","0.138%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%","0.125%"],["Target % LKP Des 2026","","40.15%","","","Akumulasi Mingguan RKAP","","5.00%","5.050%","5.100%","5.150%","5.200%","5.263%","5.325%","5.388%","5.450%","5.525%","5.600%","5.675%","5.750%","5.838%","5.925%","6.013%","6.100%","6.200%","6.300%","6.400%","6.500%","6.613%","6.725%","6.838%","6.950%","7.075%","7.200%","7.325%","7.450%","7.588%","7.725%","7.863%","8.000%","8.138%","8.275%","8.413%","8.550%","8.675%","8.800%","8.925%","9.050%","9.175%","9.300%","9.425%","9.550%","9.675%","9.800%","9.925%","10.050%"],["Nama Pimpro","","SETIAJI","","","Rencana Mingguan Proyek","","5.000%","10.000%","0.010%","0.010%","0.010%","0.015%","0.015%","0.015%","0.015%","0.010%","4.110%","5.010%","6.110%","5.025%","2.115%","2.025%","1.115%","0.010%","10.140%","4.000%","4.220%","0.080%","0.030%","0.010%","0.000%","0.090%","0.03%","0.046%","0.000%","0.000%","0.120%","0.030%","0.020%","0.000%","0.010%","0.000%","0.000%","0.000%","0.490%","0.000%","0.030%","0.010%","0.280%","0.000%","0.110%","0.300%","0.300%","0.300%","0.650%"],["Penanggungjawab Report","","KURNIAWAN","","","Akumulasi Mingguan Proyek","","5.00%","15.000%","15.010%","15.020%","15.030%","15.045%","15.060%","15.075%","15.090%","15.100%","19.210%","24.220%","30.330%","35.355%","37.470%","39.495%","40.610%","40.620%","50.760%","54.760%","58.980%","59.060%","59.090%","59.100%","59.100%","59.190%","59.220%","59.266%","59.266%","59.266%","59.386%","59.416%","59.436%","59.436%","59.446%","59.446%","59.446%","59.446%","59.936%","59.936%","59.966%","59.976%","60.256%","60.256%","60.366%","60.666%","60.966%","61.266%","61.916%"],["","","","","","Realisasi Mingguan Proyek","","5.00%","10.000%","0.010%","0.010%","0.010%","0.010%","0.010%","0.010%","0.010%","0.010%","3.110%","4.010%","4.110%","4.020%","5.110%","3.020%","2.110%","0.000%","10.060%","4.000%","4.110%","0.050%","0.100%","0.000%","0.010%","0.200%","0.040%","0.000%","0.020%","0.000%","0.080%","0.000%","0.000%","0.010%","0.000%","0.000%","0.000%","0.000%","0.000%","0.000%","0.000%","0.050%","0.050%","0.050%","0.050%","0.000%","0.000%","0.000%","0.000%"],["","","","","","Akumulasi Realisasi Proyek","","5.00%","15.000%","15.010%","15.020%","15.030%","15.040%","15.050%","15.060%","15.070%","15.080%","18.190%","22.200%","26.310%","30.330%","35.440%","38.460%","40.570%","40.570%","50.630%","54.630%","58.740%","58.790%","58.890%","58.890%","58.900%","59.100%","59.140%","59.140%","59.160%","59.160%","59.240%","","","0.010%","","","","","","","","0.050%","0.100%","0.150%","0.200%"],["","","","","","DEVIASI PROYEK VS RKAP","","","0.000%","0.000%","0.000%","0.000%","-0.005%","-0.005%","-0.005%","-0.005%","0.000%","-1.000%","-1.000%","-2.000%","-1.005%","2.995%","0.995%","0.995%","-0.010%","-0.080%","0.000%","-0.110%","-0.030%","0.070%","-0.010%","0.010%","0.110%","0.010%","-0.046%","0.020%","0.000%","-0.040%","-0.030%","-0.020%","0.010%","-0.010%","0.000%","0.000%","0.000%","-0.490%","0.000%","-0.030%","0.040%","-0.230%","0.050%","-0.060%","-0.300%","-0.300%","-0.300%","-0.650%"],["NO","Milestone","RINCIAN KEGIATAN (WBS)","PIC","DIVISI","NILAI KEGIATAN","% BOBOT KEGIATAN","A\/P","JANUARI ","","","","FEBRUARI","","","","MARET","","","","APRIL","","","","MEI","","","","JUNI","","","","JULI","","","","AGUSTUS","","","","SEPTEMBER","","","","OKTOBER","","","","NOVEMBER","","","","DESEMBER"],["","","","","","","","","TARGET PLAN DIROPS","","","0.20%","TARGET PLAN DIROPS","","","0.25%","TARGET PLAN DIROPS","","","0.30%","TARGET PLAN DIROPS","","","0.35%","TARGET PLAN DIROPS","","","0.40%","TARGET PLAN DIROPS","","","0.45%","TARGET PLAN DIROPS","","","0.50%","TARGET PLAN DIROPS","","","0.55%","TARGET PLAN DIROPS","","","0.55%","TARGET PLAN DIROPS","","","0.50%","TARGET PLAN DIROPS","","","0.50%","TARGET PLAN DIROPS","","","0.50%"],["","","","","","","","","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4","W1","W2","W3","W4"],["1","Milestone 1","Inisiasi & Inpeksi","Hendra Setiawan","Divisi Project Management","3,750,000,000","2.500%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["1","Milestone 1","Inisiasi & Inpeksi","Hendra Setiawan","Divisi Project Management","3,750,000,000","2.500%","A"],["1.1","Milestone 1","Kick-off & Charter","Budi Santoso","Divisi QA & Survey","3,750,000,000.00","2.500%","P","","2.50%"],["1.1","Milestone 1","Kick-off & Charter","Budi Santoso","Divisi QA & Survey","3,750,000,000.00","2.500%","A","","2.50%"],["2","Milestone 2","Pekerjaan Permukaan & Lambung","Rizky Pratama","Divisi Reparasi Lambung","28,500,000,000","0.000%","P"],["2","Milestone 2","Pekerjaan Permukaan & Lambung","Rizky Pratama","Divisi Reparasi Lambung","28,500,000,000","0.000%","A"],["2.1","Milestone 2","Replating Lambung\t2","Andi Wijaya","Divisi Pemotongan & Pengelasan","28,500,000,000.00","19.000%","P"],["2.1","Milestone 2","Replating Lambung\t2","Andi Wijaya","Divisi Pemotongan & Pengelasan","28,500,000,000.00","19.000%","A"],["2.1.1","Milestone 2","Penggantian Pelat Area Pelat Dasar","Andi Wijaya","Divisi Pemotongan & Pengelasan","18,000,000,000.00","12.000%","P","","","","","","4.00%","4.00%","4.00%"],["2.1.1","Milestone 2","Penggantian Pelat Area Pelat Dasar","Andi Wijaya","Divisi Pemotongan & Pengelasan","18,000,000,000.00","12.000%","A","","","","","","","4.00%","4.00%","4.00%"],["2.1.2","Milestone 2","Sandblasting & Cat Lambung","Agus Hermawan","Divisi Hydro-Blasting & Pengecatan","10,500,000,000.00","7.000%","P","","","","","","","","1.00%","2.00%","4.00%"],["2.1.2","Milestone 2","Sandblasting & Cat Lambung","Agus Hermawan","Divisi Hydro-Blasting & Pengecatan","10,500,000,000.00","7.000%","A","","","","","","","","","","3.00%","4.00%"],["3","Milestone 3","Pekerjaan Permukaan & Lambung","Rizky Pratama","Divisi Reparasi Lambung","37,500,000,000","0.000%","P"],["3","Milestone 3","Pekerjaan Permukaan & Lambung","Rizky Pratama","Divisi Reparasi Lambung","37,500,000,000","0.000%","A"],["3.1","Milestone 3","    Overhaul Mesin Utama (ME)","Dewi Lestari","Divisi Permesinan","37,500,000,000","25.000%","P"],["3.1","Milestone 3","    Overhaul Mesin Utama (ME)","Dewi Lestari","Divisi Permesinan","37,500,000,000","25.000%","A"],["3.1.1","Milestone 3","        Demontasi & Replacement Piston\/Liner","Bambang Suryo","Divisi Engine Mechanical","22,500,000,000.00","15.000%","P","","","","","","","","","","4.00%","5.00%","6.00%"],["3.1.1","Milestone 3","        Demontasi & Replacement Piston\/Liner","Bambang Suryo","Divisi Engine Mechanical","22,500,000,000.00","15.000%","A","","","","","","","","","","3.00%","4.00%","4.00%","4.00%"],["3.1.2","Milestone 3","        Alignment Shaft & Balans Propeler","Rian Hidayat","Divisi Propulsi & Shafting","15,000,000,000.00","10.000%","P","","","","","","","","","","","","","5.00%","2.00%","2.00%","1.00%"],["3.1.2","Milestone 3","        Alignment Shaft & Balans Propeler","Rian Hidayat","Divisi Propulsi & Shafting","15,000,000,000.00","10.000%","A","","","","","","","","","","","","","","5.00%","3.00%","2.00%"],["4","Milestone 4","        Modernisasi Sewaco & Kelistrikan","Irfan Mansyur","Divisi Elektrikal & Otomasi","27,000,000,000","18.000%","P"],["4","Milestone 4","        Modernisasi Sewaco & Kelistrikan","Irfan Mansyur","Divisi Elektrikal & Otomasi","27,000,000,000","18.000%","A"],["4.1","Milestone 4","        Instalasi Kabel & Navigasi","Maya Putri","Divisi Navigasi & Komunikasi","27,000,000,000","18.000%","P"],["4.1","Milestone 4","        Instalasi Kabel & Navigasi","Maya Putri","Divisi Navigasi & Komunikasi","27,000,000,000","18.000%","A"],["4.1.1","Milestone 4","        Wiring & Panel Switchboard Utama","Maya Putri","Divisi Navigasi & Komunikasi","27,000,000,000","18.000%","P","","","","","","","","","","","","","","","","","","10.00%","4.00%","4.00%"],["4.1.1","Milestone 4","        Wiring & Panel Switchboard Utama","Maya Putri","Divisi Navigasi & Komunikasi","27,000,000,000","18.000%","A","","","","","","","","","","","","","","","","","","10.00%","4.00%","4.00%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.10%","","","0.16%","","","","","","","","","","","","","","","","0.05%","0.05%","0.05%","0.05%"],["","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","0.07%"],["","","","","","","","","","","","","","","","","","","","","","","","","","0.03%","","","","","","","0.010%","","","","","","","","0.01%"],["","","","","","","","","","","","","","","","","","","","","","","","","","0.01%","","","","","","","","","0.016%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%","","","","","","","","","","0.05%"],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%","","","","","","","","","","0.05%"],[],[],[],[],[],[],[],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%","","0.03%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%","","","","","0.02%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%","","","","","","","","","0.030%","","","","0.11%","","","","0.11%","","","","","","","0.03%"],[],[],[],[],[],[],[],[],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],[],["","","","","","","","","","","","","","","","","","","","","","","","","","","","0.03%"],["2.4.6","        End User Certificate","        End User Certificate","","","0","","A"],["2.4.7","        Engineering Document","        Engineering Document","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.4.7","        Engineering Document","        Engineering Document","","","0","","A"],["2.5","    Pengadaan Integrasi OPQ","    Pengadaan Integrasi OPQ","","","0"],["2.5","    Pengadaan Integrasi OPQ","    Pengadaan Integrasi OPQ","","","0"],["2.5.1","        OPQ & RST","        OPQ & RST","","","0","","P"],["2.5.1","        OPQ & RST","        OPQ & RST","","","0","","A"],["2.5.2","        OPQ Pengadaan","        OPQ Pengadaan","","","0","","P"],["2.5.2","        OPQ Pengadaan","        OPQ Pengadaan","","","0","","A"],["2.5.3","        Purchase Order","        Purchase Order","","","0","","P"],["2.5.3","        Purchase Order","        Purchase Order","","","0","","A"],["2.5.4","        Kontrak Integrasi OPQ","        Kontrak Integrasi OPQ","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.5.4","        Kontrak Integrasi OPQ","        Kontrak Integrasi OPQ","","","0","","A"],["2.5.5","        Down Payment Integrasi OPQ","        Down Payment Integrasi OPQ","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.5.5","        Down Payment Integrasi OPQ","        Down Payment Integrasi OPQ","","","0","","A"],["2.5.6","        End User Certificate","        End User Certificate","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.5.6","        End User Certificate","        End User Certificate","","","0","","A"],["2.5.7","        Engineering Document","        Engineering Document","","","0","","P","","","","","","","","","","","","","","","","","","","","0.02%","","","","","","","","","","","","","","","","","","","","","0.01%"],["2.5.7","        Engineering Document","        Engineering Document","","","0","","A"],["2.6","    Survei Perbaikan RST 20","    Survei Perbaikan RST 20","","","0.00"],["2.6","    Survei Perbaikan RST 20","    Survei Perbaikan RST 20","","","0.00"],["2.6.1","        Scope Of Work Perbaikan","        Scope Of Work Perbaikan","","","0","","P"],["2.6.1","        Scope Of Work Perbaikan","        Scope Of Work Perbaikan","","","0","","A"],["2.7","    Pengadaan Hardware","    Pengadaan Hardware","","","0.00"],["2.7","    Pengadaan Hardware","    Pengadaan Hardware","","","0.00"],["2.7.1","        Bill of Material (BOM)","        Bill of Material (BOM)","","","0","","P","","","","","","","","","","","","","","","","","","","","0.01%"],["2.7.1","        Bill of Material (BOM)","        Bill of Material (BOM)","","","0","","A","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["2.7.2","        ZWX & STU","        ZWX & STU","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.7.2","        ZWX & STU","        ZWX & STU","","","0","","A","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.010%"],["2.7.3","        ZWX Pengadaan","        ZWX Pengadaan","","","0","","P","","","","","","","","","","","","","","","","","","","","0.03%"],["2.7.3","        ZWX Pengadaan","        ZWX Pengadaan","","","0","","A"],["2.7.4","        Purchase Order","        Purchase Order","","","0","","P","","","","","","","","","","","","","","","","","","","","0.05%"],["2.7.4","        Purchase Order","        Purchase Order","","","0","","A"],["2.7.5","        Kontrak","        Kontrak","","","0","","P","","","","","","","","","","","","","","","","","","","","","","0.02%","","","0.01%"],["2.7.5","        Kontrak","        Kontrak","","","0","","A"],["2.7.6","        Down Payment","        Down Payment","","","0","","P","","","","","","","","","","","","","","","","","","","","","","","","","","0.040%"],["2.7.6","        Down Payment","        Down Payment","","","0","","A"],["2.7.7","        Work Progress","        Engineering Document","","","0","","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.030%","","","0.01%","","","","","","0.03%"],["2.7.7","        Work Progress","        Engineering Document","","","0","","A"],["2.8","    Product Spec All Scope","    Product Spec All Scope","","","0","","P","","","","","","","","","","0.10%","","0.10%","","0.10%","","0.10%"],["2.8","    Product Spec All Scope","    Product Spec All Scope","","","0","","A","","","","","","","","","","0.10%","","0.10%","","0.10%","","0.10%"],["2.9","    Approval Desain Gambar","    Approval Desain Gambar","","","0","","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.10%"],["2.9","    Approval Desain Gambar","    Approval Desain Gambar","","","0","","A"],["2.10","    CDR Activity","    CDR Activity","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.10%"],["2.10","    CDR Activity","    CDR Activity","","","0","0.000%","A"],["3","ABCDE","ABCDE","","","0","0.000%"],["3","ABCDE","ABCDE","","","0","0.000%"],["3.1","    End User Certificate","    End User Certificate","","","0","0.000%","P","","","","","","","","","","","","","","","","","0.01%","","","","0.02%","","","","0.03%","","0.01%"],["3.1","    End User Certificate","    End User Certificate","","","0","0.000%","A"],["3.2","    Pengadaan ABCDE","    Pengadaan ABCDE","","","0.00","0.000%"],["3.2","    Pengadaan ABCDE","    Pengadaan ABCDE","","","0.00","0.000%"],["3.2.1","        Bill of Material (BOM) Pengadaan ABCDE","        Bill of Material (BOM)","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","0.01%"],["3.2.1","        Bill of Material (BOM) Pengadaan ABCDE","        Bill of Material (BOM)","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["3.2.2","        ABCDE & FGHJ Pengadaan","        ABCDE & FGHJ ","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["3.2.2","        ABCDE & FGHJ Pengadaan","        ABCDE & FGHJ ","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","","0.010%"],["3.2.3","        CDE Pengadaan ABCDE & FGHJ ","        CDE Pengadaan","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","0.010%"],["3.2.3","        CDE Pengadaan ABCDE & FGHJ ","        CDE Pengadaan","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","","0.010%"],["3.2.4","        Purchase Order Pengadaan CDE & KLM","        Purchase Order","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","0.01%","","0.010%"],["3.2.4","        Purchase Order Pengadaan CDE & KLM","        Purchase Order","","","0","0.000%","A"],["3.2.5","        Kontrak Pengadaan FGH & CDE","        Kontrak FGH & CDE","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.020%","","0.020%"],["3.2.5","        Kontrak Pengadaan FGH & CDE","        Kontrak FGH & CDE","","","0","0.000%","A"],["3.2.6","        Down Payment Pengadaan FGH & CDE","        Down Payment FGH & CDE","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%","","","","0.10%"],["3.2.6","        Down Payment Pengadaan FGH & CDE","        Down Payment FGH & CDE","","","0","0.000%","A"],["3.3","    Work Progress Report","    Work Progress Report","","","0","0.000%","P","10.00%","0.01%","0.01%","0.01%","0.02%","0.02%","0.02%","0.02%","0.01%","0.01%","0.01%","0.01%","0.02%","0.02%","0.02%","0.02%","","0.03%","","","","","","","","","","","","","","","","","","","","","","","","0.08%","","0.11%","0.30%","0.20%","0.20%","0.54%"],["3.3","    Work Progress Report","    Work Progress Report","","","0","0.000%","A","10.00%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","0.01%","","0.03%"],["4","ABCD (WP FGH)","ABCD (WP FGH)","","","0","0.000%"],["4","ABCD (WP FGH)","ABCD (WP FGH)","","","0","0.000%"],["4.1","    Pengadaan Cable","    Pengadaan Cable","","","0.00","0.000%"],["4.1","    Pengadaan Cable","    Pengadaan Cable","","","0.00","0.000%"],["4.1.1","        Bill of Material (BOM)","        Bill of Material (BOM)","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","0.01%"],["4.1.1","        Bill of Material (BOM)","        Bill of Material (BOM)","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["4.1.2","        SPH & HPS","        SPH & HPS","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["4.1.2","        SPH & HPS","        SPH & HPS","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","0.01%"],["4.1.3","        RKS Pengadaan","        RKS Pengadaan","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","0.020%"],["4.1.3","        RKS Pengadaan","        RKS Pengadaan","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","","0.020%"],["4.1.4","        Purchase Order","        Purchase Order","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%","","","0.100%"],["4.1.4","        Purchase Order","        Purchase Order","","","0","0.000%","A","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.020%","","0.020%"],["4.1.5","        Kontrak Cable","        Kontrak Cable","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.020%","","","0.10%"],["4.1.5","        Kontrak Cable","        Kontrak Cable","","","0","0.000%","A"],["4.1.6","        Down Payment Cable","        Down Payment Cable","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","0.02%","","","0.10%"],["4.1.6","        Down Payment Cable","        Down Payment Cable","","","0","0.000%","A"],["4.2","    End User Certificate KLM","    End User Certificate KLM","","","0.00","0.000%"],["4.2","    End User Certificate KLM","    End User Certificate KLM","","","0.00","0.000%"],["4.2.1","        Interface Control Document","        Interface Control Document","","","0","0.000%","P","","","","","","","","","","","","","","","","","","","","","","","0.01%","","0.03%","","","","","","","","","","","","","0.49%"],["4.2.1","        Interface Control Document","        Interface Control Document","","","0","0.000%","A"],["4.3","    Work Progress Report","    Work Progress Report","","","0","0.000%","P","","","","","","","","","","","","","0.01%","","0.01%","","","","","0.10%","","","","","","","","","","","","","","","","","","","","","","","","","","0.10%","0.10%","0.11%"],["4.3","    Work Progress Report","    Work Progress Report","","","0","0.000%","A","","","","","","","","","","","","","0.01%","","0.01%","","","","","0.09%"],["4.4","    Work Progress Report","    Work Progress Report","","","0","0.000%","P"],["4.4","    Work Progress Report","    Work Progress Report","","","0","0.000%","A"],["4.5","    Work Progress Report","    Work Progress Report","","","0","0.000%","P"],["4.5","    Work Progress Report","    Work Progress Report","","","0","0.000%","A"],["4.6","    Work Progress Report","    Work Progress Report","","","0","0.000%","P"],["4.6","    Work Progress Report","    Work Progress Report","","","0","0.000%","A"],["4.7","    Work Progress Report","    Work Progress Report","","","0","0.000%","A"],["4.7","    Work Progress Report","    Work Progress Report","","","0","0.000%","A"],["4.7.1","        Interface Control Document","        Interface Control Document","","","0","0.000%","A"]]',185,'2026-10-13 08:21:26','2026-09-21 02:47:43','2026-10-13 08:21:26');
INSERT INTO "project_links" VALUES (2,1,'6. Procurement','https://docs.google.com/spreadsheets/d/1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70','procurement','[["PROCUREMENT "],["Nama Proyek ","","Procurement Aja","","","","SPH","HPS","RKS","PR","RFQ","Negosiasi","PO","Kontrak","Production","Delivered","Jumlah PR"],["Nilai Kontrak ","","150000000000","","","",1,1,7,3,0,1,1,4,0,10,28],[],["NO","NAMA PENGADAAN","NAMA VENDOR","TARGET TGL PENGADAAN","STATUS","STATUS\nTAHAPAN","PRIORITY","JENIS","PAYMENT TYPE","PROC. POLICY","HPS","Tanggal HPS","RKS","Tanggal RKS","Pic OME","NO PR","PROC. OPS","NO PO","KONTRAK","NILAI RAB\/RAP","REALISASI","MATA UANG","EFISIENSI","DUE DATE","","DP","","","","TERMIN 1","","","","TERMIN 2","","","","TERMIN 3","","","","TERMIN 4","","","","TERMIN 5","","","","TERMIN 6","","","","PAYMENT PRIORITY","ESTIMATED DELIVERY","DELIVERY STATUS","PAYMENT STATUS"],["","","","","","","","","","","","","","","","","","","","","","","","","","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","TANGGAL SUBMIT","NILAI","GR\/SES","NO SPP","","","","DP","T1","T2","T3","T4","T5","T6"],[1,"Overall PMO & System + Ground Segment + Training","ALENIA","27\/11\/2026","In Progress","SPH","High","JASA","Transfer","DEA","123\/HPS\/UJ-1\/DHN\/XI\/2023","12 Nov 2023","142\/RKS\/UJ\/XI\/2023","22 Nov 2023","LINDA",123,"adnan",44,"DONE","92.300.123","22.300.891","EUR","0,00%","22-May-2025","","10\/08\/2026","15.500.000","GR\/2026\/08\/001","SPP-2026-0810","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","02\/06\/2028"],[2,"Optical Satellite","ALENIA","28\/11\/2026","In Progress","KONTRAK","HIGH","BARANG","Transfer","DEA","456\/HPS\/UJ-1\/DHN\/XI\/2023","12 Nov 2023","142\/RKS\/UJ\/XI\/2023","22 Nov 2023","LINDA",123,"adnan",44,"DONE","88.724.647","78.727.247","EUR","0,00%","23-May-2025","","12\/08\/2026","8.750.000","SES\/2026\/08\/014","SPP-2026-0812","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","02\/06\/2028","NOT YET"],[3," Imagery Custom Subscription","BLACKSKY LLC","03\/12\/2026","In Progress","KONTRAK","HIGH","BARANG","Transfer","DEA","786\/HPS\/UJ-1\/ILS\/XII\/2023","01 Dec 2023","169\/RKS\/UJ\/XII\/2023","07 Dec 2023","LINDA",123,"adnan",44,"DONE","$10.010.000","$10.010.000","USD","0,00%","27-Jan-2025","","15\/08\/2026","23.000.000","GR\/2026\/08\/005","SPP-2026-0815","21 Dec 2023","$250.000,00","DONE",2400000405,"04 Jan 2024","$300.000,00","DONE",2400000164,"15 Jul 2024","$250.000,00","DONE",2400000383,"","250.000,00","",2400000044,"","","","","","","","","HIGH","09\/02\/2026","DELIVERED","N\/A","NOT PAID","NOT PAID","NOT PAID"],[4,"Pengadaan Barang A ","PT Nusantara Tech Solusindo","10\/04\/2026","Done","DELIVERED","HIGH","BARANG & JASA","Transfer","DIANASTI","","","","","","","","","","","$3.225,81","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/01\/2026","DELIVERED"],[5,"Pengadaan Server & Infrastructure Modernization","CV Cipta Karya Informatika","10\/09\/2026","In Progress","NEGOSIASI","HIGH","BARANG & JASA","Transfer","DIANASTI","","","","","","","","","","","Rp 18.000.000","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/03\/2026","DELIVERED"],[5.1,"Ruggedized Marine Rackmount Server Node (Shock & Vibration Proof)","CV Cipta Karya Informatika","08\/09\/2026","Done","DELIVERED","HIGH","JASA","Transfer","DIANASTI","","","","","","","","","","","$7.741,94","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","06\/02\/2026","DELIVERED"],[5.2,"Naval Network Switch & Fiber Optic Transceiver Set (Vessel Modernization)","CV Cipta Karya Informatika","09\/09\/2026","Done","DELIVERED","HIGH","BARANG","Transfer","DIANASTI","","","","","","","","","","","S$ 3.008,47","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","03\/03\/2026","DELIVERED"],[5.3,"Marine-Grade Online UPS & Redundant Power Unit (Dual Input AC\/DC)","CV Cipta Karya Informatika","10\/09\/2026","In Progress","KONTRAK","HIGH","BARANG","Transfer","DIANASTI","","","","","","","","","","","\u20ac 4.411,76","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","27\/02\/2026","DELIVERED"],[6,"Jasa Maintenance Software Management System","PT Graha Informatika Utama","12\/09\/2026","In Progress","RKS","HIGH","BARANG & JASA","Transfer","DIANASTI","","","","","","","","","","","Rp 27.500.000","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","27\/02\/2026","DELIVERED"],[7,"Pengadaan Laptop Workstation Tim UI\/UX & Dev","PT Digital Studio Indonesia","14\/09\/2026","In Progress","RKS","","BARANG","Transfer","DIANASTI","","","","","","","","","","","$593,55","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","31\/03\/2026","DELIVERED"],[7.1,"MacBook Pro 16\" M3 Max (36GB RAM, 1TB SSD) \u2014 UI\/UX Lead & Sr. Dev","PT Digital Studio Indonesia","10\/09\/2026","Done","DELIVERED","LOW","BARANG","Transfer","DIANASTI","","","","","","","","","","","\u20ac 7.941,18","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","12\/02\/2026","DELIVERED"],[7.2,"Windows Workstation Laptop (32GB RAM, 1TB SSD, RTX Graphics) \u2014 Dev Team","PT Digital Studio Indonesia","12\/09\/2026","Done","DELIVERED","NORMAL","BARANG","Transfer","DIANASTI","","","","","","","","","","","\u20ac 5.588,24","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","31\/12\/2026","NOT YET"],[8,"Jasa Konsultasi Redesign UI\/UX Dashboard PMO","PT Jaringan Cyber Nusantara","15\/09\/2026","In Progress","HPS","NORMAL","BARANG & JASA","Transfer","DIANASTI","","","","","","","","","","","Rp 31.000.000","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","17\/03\/2026","DELIVERED"],[9,"Pengadaan Perangkat Network Switch & Router","PT Awan Data Mitra","15\/09\/2026","In Progress","RKS","HIGH","JASA","Transfer","HANA","","","","","","","","","","","$3.419,35","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","30\/06\/2026","NOT YET"],[10,"Jasa Sewa Cloud Server & Domain Tahun 2026","CV Media Global Perdana","16\/09\/2026","In Progress","PR","HIGH","BARANG","Transfer","HANA","","","","","","","","","","","$9.354,84","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","08\/06\/2026","NOT YET"],[11,"Pengadaan Lisensi Software Design & Visualizer","PT Academy Talenta Indonesia","17\/09\/2026","In Progress","RKS","HIGH","BARANG","Transfer","MARZUKI","","","","","","","","","","","Rp 27.500.000","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","29\/05\/2026","NOT YET"],[12,"Jasa Pelatihan & Sertifikasi Scrum Master","CV Mandiri Jaya Paper","17\/09\/2026","In Progress","RKS","HIGH","BARANG & JASA","Transfer","HANA","","","","","","","","","","","S$ 7.457,63","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","13\/08\/2026","NOT YET"],[13,"Pengadaan ATK & Perlengkapan Operasional Kantor","PT Secure Shield Consult","18\/09\/2026","Done","DELIVERED","HIGH","JASA","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","30\/06\/2026","NOT YET"],[14,"Jasa Audit Keamanan Siber & Penetration Testing","PT Visual Electro Perkasa","20\/09\/2026","In Progress","PR","NORMAL","BARANG","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","30\/06\/2026","NOT YET"],[15,"Pengadaan Display Monitor & Video Conference Kit","CV Cool Air Engineering","21\/09\/2026","In Progress","RKS","NORMAL","BARANG","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","07\/12\/2026","NOT YET"],[16,"Jasa Pemeliharaan Rutin AC & Fasilitas Kantor","PT Mega Data Solution","22\/09\/2026","In Progress","PR","NORMAL","BARANG & JASA","Transfer","DEA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","14\/07\/2026","NOT YET"],[17,"Jasa Langganan API Data Analytics & Visualization","PT Analitika Sistem Indonesia","23\/09\/2026","Done","DELIVERED","NORMAL","JASA","Transfer","DEA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","MEDIUM","16\/10\/2026","DELIVERED"],[18,"Pengadaan Furnitur Ergonomis Ruang Kerja","CV Mebel Utamo Design","24\/09\/2026","In Progress","RKS","","BARANG & JASA","Tunai","DEA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/05\/2029","NOT YET"],[18.1,"Kursi Kerja Ergonomis Mesh (Adjustable Lumbar Support & Headrest)","CV Mebel Utamo Design","20\/09\/2026","Done","DELIVERED","LOW","BARANG","Tunai","DEA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/10\/2025","NOT YET"],[18.2,"Meja Kerja Motorized Electric Standing Desk (Dual Motor)","CV Mebel Utamo Design","22\/09\/2026","Done","DELIVERED","LOW","BARANG & JASA","Tunai","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","23\/01\/2025","NOT YET"],[18.3,"Dual Monitor Arm Heavy Duty (Gas Spring Desk Mount)","CV Mebel Utamo Design","23\/09\/2026","Done","DELIVERED","NORMAL","JASA","Tunai","MARZUKI","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/12\/2024","NOT YET"],[18.4,"Footrest Ergonomis & Cable Management Tray Set","CV Mebel Utamo Design","25\/09\/2026","In Progress","PO","NORMAL","BARANG","Tunai","MARZUKI","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","31\/12\/2024","NOT YET"],[19,"Jasa Sewa Cloud Server & Domain Tahun 2026","PT Mega Data Solution","22\/09\/2026","Overdue","","","","Transfer","MARZUKI","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","MEDIUM","30\/06\/2025","NOT YET"],[20,"Pengadaan Lisensi Software Design & Visualizer","PT Analitika Sistem Indonesia","23\/09\/2026","Overdue","","","","Transfer","MARZUKI","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","31\/07\/2025","DELIVERED"],[21,"Jasa Pelatihan & Sertifikasi Scrum Master","","30\/09\/2026","Overdue","","","","Transfer","MARZUKI","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","30\/06\/2025","DELIVERED"],[22,"JASA JASAANYA","PT Analitika Sistem Indonesia","22\/09\/2026","Overdue","","","","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","31\/07\/2025","DELIVERED"],[23,"Jasa Raharja","PT Mencari Kopi","30\/09\/2026","Overdue","","","","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","HIGH","28\/06\/2024","DELIVERED"],[24,"Pengadaan ngada ngada","PT Ngadi Ngadi Nusantara","27\/09\/2026","Overdue","","","","Transfer","HANA","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","31\/12\/2025","DELIVERED"],[25," Jasa Pahlawan","PT Nasionalisme","03\/12\/2026","In Progress","KONTRAK","HIGH","BARANG","Transfer","DEA","786\/HPS\/UJ-1\/ILS\/XII\/2023","01 Dec 2023","169\/RKS\/UJ\/XII\/2023","07 Dec 2023","LINDA",123,"adnan",44,"DONE","$10.010.000","$10.010.000","USD","0,00%","27-Jan-2025","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","LOW","30\/05\/2025","DELIVERED"]]',40,'2026-10-13 08:21:27','2026-09-21 02:47:51','2026-10-13 08:21:27');
INSERT INTO "project_profiles" VALUES (1,1,'progress_revenue','Refurbishment & Modernisasi Sistem Senjata KRI','SETIAJI','KURNIAWAN','150000000000','2026-10-06 08:57:43','2026-10-13 03:24:28');
INSERT INTO "project_profiles" VALUES (2,1,'procurement','Procurement Aja','SETIAJI','KURNIAWAN','150,000,000,000','2026-10-07 08:42:46','2026-10-13 03:24:29');
INSERT INTO "system_syncs" VALUES (1,'progress_revenue','2026-10-13 08:21:26','2026-09-21 02:47:43','2026-10-13 08:21:26');
INSERT INTO "system_syncs" VALUES (2,'procurement','2026-10-13 08:21:27','2026-09-21 02:47:51','2026-10-13 08:21:27');
CREATE INDEX IF NOT EXISTS "cache_expiration_index" ON "cache" (
	"expiration"
);
CREATE INDEX IF NOT EXISTS "cache_locks_expiration_index" ON "cache_locks" (
	"expiration"
);
CREATE UNIQUE INDEX IF NOT EXISTS "failed_jobs_uuid_unique" ON "failed_jobs" (
	"uuid"
);
CREATE INDEX IF NOT EXISTS "jobs_queue_index" ON "jobs" (
	"queue"
);
CREATE INDEX IF NOT EXISTS "personal_access_tokens_expires_at_index" ON "personal_access_tokens" (
	"expires_at"
);
CREATE UNIQUE INDEX IF NOT EXISTS "personal_access_tokens_token_unique" ON "personal_access_tokens" (
	"token"
);
CREATE INDEX IF NOT EXISTS "personal_access_tokens_tokenable_type_tokenable_id_index" ON "personal_access_tokens" (
	"tokenable_type",
	"tokenable_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "project_profiles_new_master_project_id_type_unique" ON "project_profiles" (
	"master_project_id",
	"type"
);
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" (
	"last_activity"
);
CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" (
	"user_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "system_syncs_key_unique" ON "system_syncs" (
	"key"
);
CREATE UNIQUE INDEX IF NOT EXISTS "tasks_task_id_unique" ON "tasks" (
	"task_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (
	"email"
);
COMMIT;
