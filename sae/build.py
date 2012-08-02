#!/usr/bin/env python
import os, os.path, re
from pyark.sync import *
from pyark.ignore import *
from pyark.util import rmtree

dev_path = os.path.realpath(os.path.dirname(os.path.realpath(__file__))+'/..')
deploy_path = os.path.realpath(dev_path+'/../sae-drupal-deploy')

ignore_patterns = [
	'sae/arksaeclient\.ini',
	'sae/pyark',
	'sae/build\.py',
	'sae/changes', 
	'scripts', 
	'.*\.txt', 
	'web\.config',
	'sites/all/modules/extra',
	'sites/all/themes/extra',
]

def ignore_copy(a, b):
	if ignore_compile(a) or ignore_vcs(a):
		return True
	subpath = a[len(dev_path)+1:]
	for p in ignore_patterns:
		if re.match('^'+p+'$', subpath):
			return True
			
	return False

rmtree(deploy_path)
sync_copy(dev_path, deploy_path, ignore_copy)

sync_copy(os.path.join(dev_path, 'sae/arksaeclient.ini'), os.path.join(deploy_path, 'arksaeclient.ini'))
