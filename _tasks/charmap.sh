#!/bin/bash

ruby _tasks/generate-charmap.rb; ruby _tasks/cleanup-charmap.rb; ruby _tasks/generate-charmap-templates.rb

