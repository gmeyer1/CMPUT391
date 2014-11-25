CREATE INDEX descIndex ON images(description) INDEXTYPE IS CTXSYS.CONTEXT; 
CREATE INDEX subjIndex ON images(subject) INDEXTYPE IS CTXSYS.CONTEXT; 
CREATE INDEX placeIndex ON images(place) INDEXTYPE IS CTXSYS.CONTEXT;

CREATE OR REPlACE PROCEDURE sync_index AS
BEGIN
 ctx_ddl.sync_index('descIndex');
 ctx_ddl.sync_index('subjIndex');
 ctx_ddl.sync_index('placeIndex');
END sync_index;
/
