# Keystone Server

Welcome to the Keystone Lumen Rest Server


# Api

[namespaces](/namespaces)



# NOTES 

for a keystone GUI:
Redis keeps track of how long a key as NOT been accessed (read)
object idletime $key !!! so don't need to store that in meta

Maybe it would be useful to store the date when a key was created? or updated? both?
Not accessed since we have that with object idletime
