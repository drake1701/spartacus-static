DROP TABLE "entry";
DROP TABLE "entry_tag";
DROP TABLE "entry_queue";
DROP TABLE "tag";
DROP TABLE "image";
DROP TABLE "image_kind";
CREATE TABLE "entry_queue" (
  "id" INTEGER PRIMARY KEY ASC,
  "queue_type" varchar(255) NOT NULL,
  "label" varchar(255) NOT NULL
);
CREATE TABLE "entry" (
  "id" INTEGER PRIMARY KEY ASC,
  "title" varchar(255) NOT NULL,
  "content" text,
  "filename" varchar(255) NOT NULL,
  "url_path" varchar(255) DEFAULT NULL,
  "created_at" datetime DEFAULT NULL,
  "modified_at" datetime DEFAULT NULL,
  "published_at" datetime DEFAULT NULL,
  "queue" int(11)  DEFAULT NULL,
  "published" tinyint(4) DEFAULT NULL,
  CONSTRAINT "queue-fk" FOREIGN KEY ("queue") REFERENCES "entry_queue" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE "entry_tag" (
  "id" INTEGER PRIMARY KEY ASC,
  "entry_id" int(10)  NOT NULL,
  "tag_id" int(10)  NOT NULL,
  CONSTRAINT "entry-fk" FOREIGN KEY ("entry_id") REFERENCES "entry" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "tag-fk" FOREIGN KEY ("tag_id") REFERENCES "tag" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE "image" (
  "id" INTEGER PRIMARY KEY ASC ,
  "entry_id" int(10)  NOT NULL,
  "path" varchar(255) DEFAULT '',
  "kind" int(10)  NOT NULL,
  CONSTRAINT "kind-fk" FOREIGN KEY ("kind") REFERENCES "image_kind" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "post-fk" FOREIGN KEY ("entry_id") REFERENCES "entry" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE "image_kind" (
  "id" INTEGER PRIMARY KEY ASC,
  "path" varchar(45) NOT NULL,
  "label" varchar(45) NOT NULL,
  "is_required" tinyint(1) DEFAULT '0',
  "exclude" tinyint(1) DEFAULT '0',
  "position" int(11) DEFAULT NULL
);
CREATE TABLE "tag" (
  "id" INTEGER PRIMARY KEY ASC,
  "title" varchar(255) NOT NULL,
  "slug" varchar(255) DEFAULT NULL,
  "list" tinyint(1) DEFAULT '1',
  "count" INTEGER DEFAULT '0',
  "thumb" VARCHAR(255) DEFAULT NULL
);
CREATE INDEX "entry_tag_entry-fk" ON "entry_tag" ("entry_id");
CREATE INDEX "entry_tag_tag-fk" ON "entry_tag" ("tag_id");
CREATE INDEX "entry_url_path_UNIQUE" ON "entry" ("url_path");
CREATE INDEX "queue-fk" ON "entry" ("queue");
CREATE INDEX "image_entry" ON "image" ("entry_id");
CREATE INDEX "image_entry_id" ON "image" ("entry_id","kind");
CREATE INDEX "image_kind-fk" ON "image" ("kind");
CREATE INDEX "image_post-fk" ON "image" ("entry_id");
