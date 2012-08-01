#!/usr/bin/env python
import os, os.path, re
from pyark.sync import *
from pyark.ignore import *
from pyark.util import rmtree

dev_path = os.path.realpath(os.path.dirname(os.path.realpath(__file__))+'/..')
deploy_path = os.path.realpath(dev_path+'/../sae-drupal-deploy')

ignore_patterns = ['sae', 'scripts', '.*\.txt', 'web\.config']

def ignore_copy(a, b):
	if ignore_compile(a) or ignore_vcs(a):
		return True
	subpath = a[len(dev_path)+1:]
	for p in ignore_patterns:
		if re.match('^'+p+'$', subpath):
			return True
			
	return False

try:
	rmtree(deploy_path)
except:
	pass

sync_copy(dev_path, deploy_path, ignore_copy)
